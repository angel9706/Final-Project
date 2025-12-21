        </main>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">Change Password</h3>
                <button id="closePasswordModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="changePasswordForm">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                        <input type="password" id="currentPassword" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <input type="password" id="newPassword" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <input type="password" id="confirmPassword" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" id="cancelPasswordChange" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Common Scripts -->
    <script>
    $(document).ready(function() {
        // Initialize accordion
        initAccordion();
        
        // Initialize sidebar
        initSidebar();
        
        // Change password modal
        $('#changePasswordBtn').on('click', function() {
            $('#changePasswordModal').removeClass('hidden');
        });
        
        $('#closePasswordModal, #cancelPasswordChange').on('click', function() {
            $('#changePasswordModal').addClass('hidden');
            $('#changePasswordForm')[0].reset();
        });
        
        // Change password form submit
        $('#changePasswordForm').on('submit', function(e) {
            e.preventDefault();
            handlePasswordChange();
        });
        
        // Logout
        $('#logoutBtn').on('click', function() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '/siapkak/public/auth/logout.php';
            }
        });
    });

    function initAccordion() {
        $('.accordion-toggle').on('click', function() {
            const content = $(this).next('.accordion-content');
            const icon = $(this).find('.accordion-icon');
            
            // Close other accordions
            $('.accordion-content').not(content).removeClass('open');
            $('.accordion-icon').not(icon).removeClass('rotate-180');
            
            // Toggle current accordion
            content.toggleClass('open');
            icon.toggleClass('rotate-180');
        });
    }

    function initSidebar() {
        const sidebar = $('#sidebar');
        const overlay = $('#sidebarOverlay');
        const toggle = $('#sidebarToggle');

        toggle.on('click', function() {
            sidebar.toggleClass('open');
            overlay.toggleClass('hidden');
        });

        overlay.on('click', function() {
            sidebar.removeClass('open');
            overlay.addClass('hidden');
        });
    }

    function handlePasswordChange() {
        const currentPassword = $('#currentPassword').val();
        const newPassword = $('#newPassword').val();
        const confirmPassword = $('#confirmPassword').val();
        
        if (newPassword !== confirmPassword) {
            alert('New passwords do not match!');
            return;
        }
        
        if (newPassword.length < 6) {
            alert('New password must be at least 6 characters!');
            return;
        }
        
        $.ajax({
            url: '/siapkak/public/auth/change-password.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            }),
            success: function(response) {
                if (response.success) {
                    alert('Password changed successfully!');
                    $('#changePasswordModal').addClass('hidden');
                    $('#changePasswordForm')[0].reset();
                } else {
                    alert(response.message || 'Failed to change password');
                }
            },
            error: function() {
                alert('Error changing password');
            }
        });
    }

    // Alert helper function
    function showAlert(message, type = 'info') {
        const alertClass = {
            success: 'bg-green-100 border-green-500 text-green-900',
            error: 'bg-red-100 border-red-500 text-red-900',
            warning: 'bg-yellow-100 border-yellow-500 text-yellow-900',
            info: 'bg-blue-100 border-blue-500 text-blue-900'
        };
        
        const alert = $('<div>')
            .addClass(`border-l-4 p-4 mb-4 ${alertClass[type]}`)
            .html(`<p class="font-medium">${message}</p>`)
            .hide()
            .fadeIn();
        
        $('main').prepend(alert);
        
        setTimeout(() => {
            alert.fadeOut(() => alert.remove());
        }, 5000);
    }
    </script>
    
    <?php if (isset($additionalScripts)) echo $additionalScripts; ?>
</body>
</html>
