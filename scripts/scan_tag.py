import socket
import time
import threading
from http.server import HTTPServer, BaseHTTPRequestHandler
import json
from urllib.parse import urlparse, parse_qs

HOST = '192.168.1.199'  # IP of your laptop
PORT = 8800             # RFID controller port
WEB_PORT = 5001         # Web server port for your PHP to call
COOLDOWN = 1.0          # seconds

# Store pending scan requests
pending_requests = []
rfid_connection = None

class RFIDHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path == '/wait-for-scan':
            # This endpoint waits for the next RFID scan
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.end_headers()
            
            print("🔄 Waiting for RFID scan...")
            
            # Create a request object to track this scan request
            request_data = {'timestamp': time.time(), 'response_sent': False}
            pending_requests.append(request_data)
            
            # Wait up to 30 seconds for a scan
            timeout = 30
            start_time = time.time()
            
            while (time.time() - start_time) < timeout:
                if 'tag' in request_data:
                    # We got a tag!
                    response = {'success': True, 'rfid_tag': request_data['tag']}
                    self.wfile.write(json.dumps(response).encode())
                    print(f"✅ Sent tag to client: {request_data['tag']}")
                    return
                time.sleep(0.1)  # Check every 100ms
            
            # Timeout - no scan received
            response = {'success': False, 'error': 'No RFID scan received within 10 seconds'}
            self.wfile.write(json.dumps(response).encode())
            print("⏰ Scan request timed out")
            
        elif self.path == '/status':
            # Check if RFID is connected
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.end_headers()
            
            response = {'connected': rfid_connection is not None}
            self.wfile.write(json.dumps(response).encode())
            
        else:
            self.send_response(404)
            self.end_headers()

def start_web_server():
    """Start simple web server for PHP to call"""
    server = HTTPServer(('0.0.0.0', WEB_PORT), RFIDHandler)
    print(f"🌐 Web server started on port {WEB_PORT}")
    server.serve_forever()

def start_rfid_server():
    """Start TCP server and wait for RFID client"""
    global rfid_connection, pending_requests
    
    while True:
        try:
            server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            server.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
            
            while True:
                try:
                    server.bind((HOST, PORT))
                    break
                except OSError as e:
                    print(f"⚠️ Bind failed ({e}), waiting for IP to be ready...")
                    time.sleep(3)
            
            server.listen(1)
            print(f"🔗 RFID server listening on {HOST}:{PORT}")
            
            conn, addr = server.accept()
            print(f"📡 Connected by {addr}")
            rfid_connection = conn
            
            enable_keepalive(conn)
            handle_rfid_client(conn)
            
        except Exception as e:
            print(f"⚠️ Server error: {e}. Retrying in 5 seconds...")
            rfid_connection = None
            time.sleep(5)

def enable_keepalive(sock):
    """Enable TCP keepalive with short intervals for fast disconnect detection."""
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_KEEPALIVE, 1)
    sock.setsockopt(socket.IPPROTO_TCP, socket.TCP_KEEPIDLE, 3)
    sock.setsockopt(socket.IPPROTO_TCP, socket.TCP_KEEPINTVL, 3)
    sock.setsockopt(socket.IPPROTO_TCP, socket.TCP_KEEPCNT, 2)

def handle_rfid_client(conn):
    """Handle connected RFID client."""
    global rfid_connection, pending_requests
    last_seen = {}
    
    while True:
        try:
            data = conn.recv(1024)
            if not data:
                raise ConnectionResetError
                
            if b'\x02' in data and b'\x03' in data:
                start = data.find(b'\x02') + 1
                end = data.find(b'\x03', start)
                epc_raw = data[start:end]
                
                try:
                    epc = epc_raw.decode('ascii').strip()
                    now = time.time()
                    
                    if epc and (epc not in last_seen or (now - last_seen[epc]) > COOLDOWN):
                        print("✅ EPC Tag:", epc)
                        last_seen[epc] = now
                        
                        # Send this tag to any pending requests
                        for request in pending_requests[:]:  # Copy list to avoid modification during iteration
                            if not request.get('response_sent'):
                                request['tag'] = epc
                                request['response_sent'] = True
                                print(f"📤 Tag sent to pending request")
                        
                        # Clean up old requests
                        pending_requests = [r for r in pending_requests if (now - r['timestamp']) < 35]
                        
                except UnicodeDecodeError:
                    print("⚠️ Invalid data received")
                    
        except (ConnectionResetError, OSError):
            print("❌ Connection lost. Reconnecting...")
            rfid_connection = None
            conn.close()
            break

if __name__ == "__main__":
    print("🚀 Starting RFID Scanner...")
    print(f"📍 RFID listening on {HOST}:{PORT}")
    print(f"🌐 Web endpoint: http://{HOST}:{WEB_PORT}/wait-for-scan")
    
    # Start web server in background thread
    web_thread = threading.Thread(target=start_web_server, daemon=True)
    web_thread.start()
    
    # Start RFID server in main thread
    start_rfid_server()