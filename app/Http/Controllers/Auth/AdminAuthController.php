<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            // Regenerate session to prevent fixation attacks
            $request->session()->regenerate();

            $admin = Auth::guard('admin')->user();
            $adminId = $admin->id ?? $admin->admin_id ?? null;
            $username = $admin->username ?? 'Unknown';

            activity_log('admin', $adminId, 'login', "Admin '{$username}' logged in");

            // Store admin ID in session
            session(['admin_id' => $adminId]);

            // --- Determine first-available destination based on permissions ---
            $perms = json_decode($admin->permissions ?? '[]', true) ?: [];

            // Group definitions (main => children). Adjust if you have different children.
            $groups = [
                'dashboard' => ['dashboard', 'analytics_dashboard', 'live_attendance', 'manage_guest', 'manage_guest_tag'],
                'parking_slots' => ['parking_slots', 'manage_map', 'add_parking_area', 'edit_parking_area'],
                'violation_tracking' => ['violation_tracking', 'create_report', 'pending_reports', 'approved_reports', 'for_endorsement', 'submit_approved_report'],
                'users' => ['users', 'users_table', 'vehicles_table', 'admins_table', 'create_user', 'edit_user', 'create_admin', 'edit_admin'],
                'sticker_generator' => ['sticker_generator', 'generate_sticker', 'manage_sticker'],
                'activity_log' => ['activity_log', 'system_logs', 'entry_exit_logs', 'unknown_tags'],
            ];

            // Map each main section to a route (either path or route() name). Replace these with your actual routes.
            $routeMap = [
                'dashboard' => '/admin-dashboard',        // or route('admin.dashboard')
                'parking_slots' => '/parking-slots',         // adjust
                'violation_tracking' => '/violation-tracking',    // adjust
                'users' => '/users',           // adjust
                'sticker_generator' => '/sticker-generator',     // adjust
                'activity_log' => '/activity-log',          // adjust
            ];

            // Find first available main page based on permissions
            $destination = null;
            foreach ($groups as $main => $children) {
                // if admin has any of the permissions in this group, choose this main
                foreach ($children as $p) {
                    if (in_array($p, $perms, true)) {
                        $destination = $routeMap[$main] ?? null;
                        break 2; // stop both loops
                    }
                }
            }

            // If no matching permission found, fallback to admin dashboard path (or logout)
            if (!$destination) {
                // Option A: send to dashboard anyway (if you want)
                // $destination = '/admin-dashboard';

                // Option B: deny access — logout and redirect with message
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login.selection')
                    ->withErrors(['error' => 'You do not have access to any admin pages. Contact a super-admin.']);
            }

            // Use Laravel intended logic (if the user was redirected to login originally), otherwise go to $destination
            return redirect()->intended($destination);
        }

        return back()->withErrors(['error' => 'Invalid credentials']);
    }

    public function logout()
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            $adminId = $admin->id ?? $admin->admin_id ?? null;
            $username = $admin->username ?? 'Unknown';
            activity_log('admin', $adminId, 'logout', "Admin '{$username}' logged out");
            Auth::guard('admin')->logout();

            // Clear the session
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return redirect()->route('login.selection');
    }

    public function showLoginForm()
    {
        return view('auth.admin-login');
    }
}