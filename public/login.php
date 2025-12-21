<?php
// Login page - no breadcrumb needed
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAPKAK - Login</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    <link rel="shortcut icon" type="image/png" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    <link rel="apple-touch-icon" href="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-600 to-blue-800 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <!-- Login Card -->
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <!-- Logo -->
                <div class="mb-4">
                    <img src="img/logo.png" alt="SIAPKAK Logo" class="w-24 h-24 mx-auto object-contain">
                </div>
                <h1 class="text-4xl font-bold text-blue-600 mb-2">SIAPKAK</h1>
                <p class="text-gray-600 text-sm">Sistem Information Air Pollution Kampus Area Karawang</p>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="space-y-4">
                <!-- Email Input -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Email</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            placeholder="Masukkan email Anda"
                            required
                        >
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="w-full pl-10 pr-12 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            placeholder="Masukkan password Anda"
                            required
                        >
                        <button type="button" onclick="togglePassword('password', 'loginPwdIcon')" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                            <i id="loginPwdIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Alert Message -->
                <div id="alertMessage" class="hidden p-4 rounded-lg text-sm"></div>

                <!-- Login Button -->
                <button 
                    type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition duration-200"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i> Login
                </button>
            </form>

             <!-- Register Link  -->
            <div class="mt-6 text-center">
                <p class="text-gray-600 text-sm">
                    Belum punya akun? 
                    <a href="#" id="registerLink" class="text-blue-600 hover:text-blue-700 font-semibold">
                        Daftar di sini
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-white text-xs">
            <p>© 2025 SIAPKAK - All rights reserved</p>
        </div>
    </div>

    <!-- Registration Modal -->
    <div id="registerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-2xl p-8 w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-blue-600">Daftar Akun Baru</h2>
                <button type="button" class="text-gray-400 hover:text-gray-600 text-xl" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="registerForm" class="space-y-4">
                <!-- Name Input -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Nama Lengkap</label>
                    <input 
                        type="text" 
                        id="regName" 
                        name="name" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                        placeholder="Nama Anda"
                        required
                    >
                </div>

                <!-- Email Input -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Email</label>
                    <input 
                        type="email" 
                        id="regEmail" 
                        name="email" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                        placeholder="contoh@email.com"
                        required
                    >
                </div>

                <!-- Password Input -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="regPassword" 
                            name="password" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            placeholder="Minimal 6 karakter"
                            required
                        >
                        <button type="button" onclick="togglePassword('regPassword', 'regPwdIcon')" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                            <i id="regPwdIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password Input -->
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Konfirmasi Password</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="regConfirmPassword" 
                            name="confirm_password" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            placeholder="Ulangi password"
                            required
                        >
                        <button type="button" onclick="togglePassword('regConfirmPassword', 'regConfPwdIcon')" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                            <i id="regConfPwdIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Alert -->
                <div id="regAlertMessage" class="hidden p-4 rounded-lg text-sm"></div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition duration-200"
                >
                    <i class="fas fa-user-plus mr-2"></i> Daftar
                </button>
            </form>
        </div>
    </div>

    <script>
        // Show Alert
        function showAlert(message, type = 'error') {
            const alertBox = $('#alertMessage');
            alertBox.removeClass('hidden bg-red-50 bg-green-50 text-red-800 text-green-800 border-red-200 border-green-200');
            
            if (type === 'error') {
                alertBox.addClass('bg-red-50 text-red-800 border border-red-200');
            } else {
                alertBox.addClass('bg-green-50 text-green-800 border border-green-200');
            }
            
            alertBox.html(message);
            alertBox.removeClass('hidden');
        }

        // Show Registration Alert
        function showRegAlert(message, type = 'error') {
            const alertBox = $('#regAlertMessage');
            alertBox.removeClass('hidden bg-red-50 bg-green-50 text-red-800 text-green-800 border-red-200 border-green-200');
            
            if (type === 'error') {
                alertBox.addClass('bg-red-50 text-red-800 border border-red-200');
            } else {
                alertBox.addClass('bg-green-50 text-green-800 border border-green-200');
            }
            
            alertBox.html(message);
            alertBox.removeClass('hidden');
        }

        // Login Form Submit
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            
            const email = $('#email').val();
            const password = $('#password').val();

            $.ajax({
                url: '/siapkak/public/auth/login.php',
                type: 'POST',
                contentType: 'application/json',
                xhrFields: {
                    withCredentials: true
                },
                data: JSON.stringify({
                    email: email,
                    password: password
                }),
                success: function(response) {
                    if (response.success) {
                        // Save user to localStorage (for display purposes)
                        localStorage.setItem('user', JSON.stringify(response.data.user));
                        
                        // Redirect to dashboard (clean URL)
                        window.location.href = '/siapkak/dashboard';
                    }
                },
                error: function(xhr) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        let errorMessage = '';
                        
                        if (response.errors) {
                            // Handle validation errors
                            Object.keys(response.errors).forEach(function(key) {
                                errorMessage += response.errors[key] + ' ';
                            });
                            showAlert('❌ ' + errorMessage);
                        } else {
                            // Handle general error
                            showAlert('❌ ' + (response.message || 'Login gagal. Silakan cek email dan password Anda.'));
                        }
                    } catch(e) {
                        console.error('Login error:', e);
                        showAlert('❌ Login gagal. Silakan coba lagi.');
                    }
                }
            });
        });

        // Register Modal Toggle
        $('#registerLink').on('click', function(e) {
            e.preventDefault();
            $('#registerModal').removeClass('hidden');
        });

        $('#closeModal').on('click', function() {
            $('#registerModal').addClass('hidden');
        });

        $('#backToLogin').on('click', function(e) {
            e.preventDefault();
            $('#registerModal').addClass('hidden');
        });

        // Toggle Password Visibility
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Register Form Submit
        $('#registerForm').on('submit', function(e) {
            e.preventDefault();
            
            const name = $('#regName').val();
            const email = $('#regEmail').val();
            const password = $('#regPassword').val();
            const confirmPassword = $('#regConfirmPassword').val();

            // Validation
            if (password.length < 6) {
                showRegAlert('❌ Password minimal 6 karakter');
                return;
            }

            if (password !== confirmPassword) {
                showRegAlert('❌ Password dan konfirmasi password tidak sama');
                return;
            }

            $.ajax({
                url: '/siapkak/api/auth/register',
                type: 'POST',
                contentType: 'application/json',
                xhrFields: {
                    withCredentials: true
                },
                data: JSON.stringify({
                    name: name,
                    email: email,
                    password: password
                }),
                success: function(response) {
                    if (response.success) {
                        showRegAlert('✅ Pendaftaran berhasil! Redirecting...', 'success');
                        
                        // Redirect to dashboard after 1 second
                        setTimeout(function() {
                            window.location.href = '/siapkak/dashboard';
                        }, 1000);
                    }
                },
                error: function(xhr) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        let errorMessage = '';
                        
                        if (response.errors) {
                            // Handle validation errors
                            Object.keys(response.errors).forEach(function(key) {
                                errorMessage += response.errors[key] + '<br>';
                            });
                            showRegAlert('❌ ' + errorMessage);
                        } else {
                            // Handle general error
                            showRegAlert('❌ ' + (response.message || 'Pendaftaran gagal.'));
                        }
                    } catch(e) {
                        console.error('Register error:', e);
                        showRegAlert('❌ Pendaftaran gagal. Silakan coba lagi.');
                    }
                }
            });
        });

        // Close modal when clicking outside
        $('#registerModal').on('click', function(e) {
            if (e.target === this) {
                $(this).addClass('hidden');
            }
        });

        // Check if already logged in (session-based)
        $(document).ready(function() {
            $.ajax({
                url: '/siapkak/public/auth/check.php',
                type: 'GET',
                xhrFields: {
                    withCredentials: true
                },
                success: function(response) {
                    if (response.success) {
                        // Session valid, redirect to dashboard
                        localStorage.setItem('user', JSON.stringify(response.data.user));
                        window.location.href = '/siapkak/dashboard';
                    }
                },
                error: function() {
                    // Not logged in, stay on login page
                    localStorage.removeItem('user');
                }
            });
        });
    </script>
</body>
</html>
