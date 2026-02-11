<?php // auth/login.php ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login • Item Request System</title>
    <link rel="icon" href="../assets/img/La-Rose-Official-Logo-Revised.jpg" type="image/jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f472b6',
                        'primary-dark': '#ec4899',
                        secondary: '#a78bfa',
                        success: '#34d399',
                        warning: '#fbbf24',
                        danger: '#f87171'
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-glow': 'pulse-glow 2s ease-in-out infinite',
                        'bounce-in': 'bounce-in 0.6s ease-out'
                    }
                }
            }
        }
    </script>
    <style>
        /* Essential CSS for Modals to function correctly (derived from original logic) */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal {
            background: white;
            padding: 2rem;
            border-radius: 1.5rem;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        /* Glassmorphism utility */
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Form Controls */
        .form-input {
            width: 100%;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-input:focus {
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(244, 114, 182, 0.3);
            border-color: #f472b6;
        }

        /* Button Gradients */
        .btn-primary {
            width: 100%;
            background: linear-gradient(to right, #ec4899, #d946ef);
            color: white;
            border-radius: 0.5rem;
            transition: all 0.3s;
            box-shadow: 0 4px 6px -1px rgba(236, 72, 153, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(236, 72, 153, 0.4);
        }

        .btn-secondary {
            width: 100%;
            background: #f3f4f6;
            color: #374151;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        /* Animations */
        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        @keyframes pulse-glow {

            0%,
            100% {
                box-shadow: 0 0 15px rgba(244, 114, 182, 0.2);
            }

            50% {
                box-shadow: 0 0 25px rgba(244, 114, 182, 0.5);
            }
        }

        @keyframes bounce-in {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }

            60% {
                transform: scale(1.05);
                opacity: 1;
            }

            100% {
                transform: scale(1);
            }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

    <div class="fixed inset-0 -z-10">
        <div
            class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-gradient-to-r from-pink-300 to-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float">
        </div>
        <div class="absolute top-[-10%] right-[-10%] w-96 h-96 bg-gradient-to-r from-purple-300 to-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float"
            style="animation-delay: 2s;"></div>
        <div class="absolute bottom-[-20%] left-[20%] w-96 h-96 bg-gradient-to-r from-yellow-200 to-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float"
            style="animation-delay: 4s;"></div>
        <div class="absolute bottom-[-10%] right-[10%] w-72 h-72 bg-gradient-to-r from-green-200 to-blue-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float"
            style="animation-delay: 1s;"></div>
    </div>

    <div
        class="w-full max-w-5xl glass-panel rounded-3xl overflow-hidden flex flex-col md:flex-row shadow-2xl relative z-10 animate-bounce-in">

        <div
            class="w-full md:w-5/12 bg-gradient-to-br from-pink-400 via-pink-300 to-purple-300 p-12 text-white flex flex-col justify-center items-center relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent"></div>
            <div class="absolute inset-0 backdrop-blur-[1px]"></div>

            <div class="relative z-10 text-center space-y-6">
                <div
                    class="w-24 h-24 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center mx-auto animate-pulse-glow shadow-2xl">
                    <i class="fas fa-spa text-5xl text-white drop-shadow-lg"></i>
                </div>

                <div class="space-y-2">
                    <h1
                        class="text-5xl font-black tracking-wide bg-gradient-to-r from-white to-pink-100 bg-clip-text text-transparent drop-shadow-sm">
                        La Rose Noire
                    </h1>
                    <p class="text-pink-50 text-xl font-medium leading-relaxed">
                        Facilities Management Department
                    </p>
                </div>

                <div class="flex items-center justify-center gap-2 pt-4">
                    <div class="w-12 h-1 bg-white/60 rounded-full"></div>
                    <div class="w-8 h-1 bg-white/40 rounded-full"></div>
                    <div class="w-4 h-1 bg-white/20 rounded-full"></div>
                </div>
            </div>

            <div class="absolute top-8 right-8 w-16 h-16 bg-white/10 rounded-full flex items-center justify-center animate-float"
                style="animation-delay: 1s;">
                <i class="fas fa-star text-yellow-200 text-lg"></i>
            </div>
            <div class="absolute bottom-12 left-8 w-12 h-12 bg-white/10 rounded-full flex items-center justify-center animate-float"
                style="animation-delay: 3s;">
                <i class="fas fa-heart text-pink-200 text-sm"></i>
            </div>
        </div>

        <div class="w-full md:w-7/12 p-12 bg-gradient-to-br from-white/80 to-white/60 backdrop-blur-xl">
            <div class="space-y-8">
                <div class="text-center space-y-3">
                    <h2
                        class="text-4xl font-bold text-gray-800 bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                        Welcome Back
                    </h2>
                    <p class="text-gray-500 text-lg leading-relaxed">
                        Please enter your details to access your dashboard
                    </p>
                    <div class="w-16 h-1 bg-gradient-to-r from-pink-400 to-purple-400 rounded-full mx-auto"></div>
                </div>

                <form action="login_action.php" method="POST" class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <i class="fas fa-user text-pink-400"></i>
                            Username
                        </label>
                        <div class="relative">
                            <input type="text" name="username" required
                                class="form-input pl-12 pr-4 py-4 text-gray-700 placeholder-gray-400"
                                placeholder="Enter your username">
                            <div
                                class="absolute left-4 top-1/2 -translate-y-1/2 w-6 h-6 bg-gradient-to-r from-pink-400 to-purple-400 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <i class="fas fa-lock text-pink-400"></i>
                            Password
                        </label>
                        <div class="relative">
                            <input type="password" name="password" required
                                class="form-input pl-12 pr-12 py-4 text-gray-700 placeholder-gray-400"
                                placeholder="••••••••" id="password">
                            <div
                                class="absolute left-4 top-1/2 -translate-y-1/2 w-6 h-6 bg-gradient-to-r from-pink-400 to-purple-400 rounded-full flex items-center justify-center">
                                <i class="fas fa-lock text-white text-xs"></i>
                            </div>
                            <button type="button" onclick="togglePassword()"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-pink-500 transition-colors p-1">
                                <i class="fas fa-eye text-sm" id="password-toggle"></i>
                            </button>
                        </div>

                        <div class="flex justify-end">
                            <button type="button" id="forgot-link"
                                class="text-sm font-semibold text-pink-500 hover:text-pink-600 transition-all duration-300 flex items-center gap-2 group">
                                <i class="fas fa-key text-xs group-hover:rotate-12 transition-transform"></i>
                                Forgot Password?
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <button type="submit" class="btn-primary w-auto px-8 py-2.5 text-base font-semibold group">
                            <span>Sign In</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </form>

                <div class="pt-8 border-t border-gray-200/50 text-center">
                    <p class="text-gray-500 text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-shield-alt text-green-500"></i>
                        Secure access to your workspace
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div id="forgot-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-pink-100 to-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-key text-2xl text-pink-500"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Reset Password</h3>
                        <p class="text-sm text-gray-600">Password recovery</p>
                    </div>
                </div>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body text-center">
                <p class="text-gray-600 mb-6 leading-relaxed">
                    For security reasons, please contact the IT department directly to reset your credentials.
                </p>
                <button class="btn-secondary" onclick="closeModal()">
                    <i class="fas fa-check mr-2"></i>Understood
                </button>
            </div>
        </div>
    </div>

    <div id="error-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-red-100 to-pink-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-500"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Authentication Error</h3>
                        <p class="text-sm text-gray-600">Login failed</p>
                    </div>
                </div>
                <button class="modal-close" onclick="closeErrorModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body text-center">
                <p class="text-gray-600 mb-6 leading-relaxed">
                    Invalid Username or Password. Please check your credentials and try again.
                </p>
                <button class="btn-primary" onclick="closeErrorModal()">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>

    <script>
        // Password Toggle
        function togglePassword() {
            const password = document.getElementById('password');
            const toggle = document.getElementById('password-toggle');
            if (password.type === 'password') {
                password.type = 'text';
                toggle.className = 'fas fa-eye-slash text-sm';
            } else {
                password.type = 'password';
                toggle.className = 'fas fa-eye text-sm';
            }
        }

        // Modal Functions
        function openModal() {
            document.getElementById('forgot-modal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('forgot-modal').classList.remove('active');
        }

        function openErrorModal() {
            document.getElementById('error-modal').classList.add('active');
        }

        function closeErrorModal() {
            document.getElementById('error-modal').classList.remove('active');
            // Remove error parameter from URL without reloading
            const url = new URL(window.location);
            url.searchParams.delete('error');
            window.history.replaceState({}, '', url);
        }

        // Event Listeners
        document.getElementById('forgot-link').addEventListener('click', openModal);

        // Keyboard Navigation
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeModal();
                closeErrorModal();
            }
        });

        // Close modal when clicking overlay
        document.getElementById('forgot-modal').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });

        document.getElementById('error-modal').addEventListener('click', function (e) {
            if (e.target === this) closeErrorModal();
        });

        // Check for error parameter in URL and show modal
        window.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('error') === 'invalid_credentials') {
                openErrorModal();
            }
        });
    </script>
</body>

</html>