import socket
import time
import requests

HOST = '192.168.1.199'  # IP of my laptop
PORT = 8800
COOLDOWN = 1.0  # seconds

def start_server():
    """Start TCP server and wait for client. - Main Gate"""
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
            print(f"✅ Main Gate TCP Server started on {HOST}:{PORT}, waiting for RFID module connection...")
            conn, addr = server.accept()
            print(f"🔗 Connected by {addr}")

            enable_keepalive(conn)
            handle_client(conn)
        except Exception as e:
            print(f"⚠️ Server error: {e}. Retrying in 5 seconds...")
            time.sleep(5)

def enable_keepalive(sock):
    """Enable TCP keepalive with short intervals for fast disconnect detection."""
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_KEEPALIVE, 1)
    sock.setsockopt(socket.IPPROTO_TCP, socket.TCP_KEEPIDLE, 3)     # Wait 3s before starting probes
    sock.setsockopt(socket.IPPROTO_TCP, socket.TCP_KEEPINTVL, 3)    # Interval between probes
    sock.setsockopt(socket.IPPROTO_TCP, socket.TCP_KEEPCNT, 2)      # Number of failed probes before closing

def handle_client(conn):
    """Handle connected RFID client."""
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
                        print("📡 EPC Tag:", epc)
                        last_seen[epc] = now
                        try:
                            requests.post('http://127.0.0.1:8000/api/rfid', json={'epc': epc})

				
                        except:
                            print("❌ Failed to send data to API")
                except UnicodeDecodeError:
                    print("⚠️ Invalid data received")
        except (ConnectionResetError, OSError):
            print("❌ Connection lost. Reconnecting...")
            conn.close()
            break

if __name__ == "__main__":
    start_server()
