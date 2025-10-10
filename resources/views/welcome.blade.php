<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Tasker - Your Task Management Solution</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */
                /* Base Tailwind styles */
            </style>
        @endif
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
        <header class="fixed w-full bg-white dark:bg-gray-800 shadow-sm">
            <nav class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">Tasker</span>
                    </div>
                </div>
            </nav>
        </header>

        <main class="pt-24">
            <!-- Hero Section -->
            <section class="container mx-auto px-6 py-12">
                <div class="flex flex-col lg:flex-row items-center">
                    <div class="lg:w-1/2 lg:pr-12">
                        <h1 class="text-4xl lg:text-5xl font-bold mb-6">
                            Manage Tasks<br>
                            <span class="text-blue-600 dark:text-blue-400">Like Never Before</span>
                        </h1>
                        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
                            Tasker helps teams and individuals organize, track, and manage their work in one place. Simple, flexible, and powerful.
                        </p>
                        <div class="flex space-x-4">
                            <a href="#" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Get Started Free</a>
                            <a href="#features" class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-gray-400 dark:hover:border-gray-500 transition">Learn More</a>
                        </div>
                    </div>
                    <div class="lg:w-1/2 mt-12 lg:mt-0">
                        <img src="https://images.unsplash.com/photo-1611224923853-80b023f02d71?w=800&h=600&fit=crop" alt="Task Management" class="w-full rounded-lg shadow-lg">
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section id="features" class="bg-white dark:bg-gray-800 py-20">
                <div class="container mx-auto px-6">
                    <h2 class="text-3xl font-bold text-center mb-12">Why Choose Tasker?</h2>
                    
                    <div class="grid md:grid-cols-3 gap-8">
                        <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold mb-2">Task Organization</h3>
                            <p class="text-gray-600 dark:text-gray-400">Create, organize and prioritize tasks with ease. Keep everything in order.</p>
                        </div>

                        <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold mb-2">Team Collaboration</h3>
                            <p class="text-gray-600 dark:text-gray-400">Work together seamlessly with your team. Share tasks and track progress.</p>
                        </div>

                        <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold mb-2">Progress Tracking</h3>
                            <p class="text-gray-600 dark:text-gray-400">Monitor progress and productivity with detailed analytics and reports.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="py-20">
                <div class="container mx-auto px-6 text-center">
                    <h2 class="text-3xl font-bold mb-4">Ready to Get Started?</h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">Join thousands of teams already using Tasker to improve their productivity.</p>
                    <a href="#" class="px-8 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition inline-block">Start Your Free Trial</a>
                </div>
            </section>
        </main>

        <footer class="bg-gray-50 dark:bg-gray-800 py-12">
            <div class="container mx-auto px-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-gray-600 dark:text-gray-400">&copy; {{ date('Y') }} Tasker. All rights reserved.</div>
                    <div class="flex space-x-6 mt-4 md:mt-0">
                        <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Privacy Policy</a>
                        <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Terms of Service</a>
                        <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Contact</a>
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>
