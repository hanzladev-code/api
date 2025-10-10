<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>LogicPulse - Affiliate Marketing Revolutionized</title>

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
                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">LogicPulse</span>
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
                        Power Up Your Earnings<br>
                        <span class="text-blue-600 dark:text-blue-400">With Smarter Affiliate Marketing</span>
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
                        LogicPulse empowers affiliates and brands to connect, track, and optimize campaigns for
                        unparalleled success. Data-driven, transparent, and scalable.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Join
                            LogicPulse</a>
                        <a href="#features"
                            class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-gray-400 dark:hover:border-gray-500 transition">Learn
                            More</a>
                    </div>
                </div>
                <div class="lg:w-1/2 mt-12 lg:mt-0">
                    <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800&h=600&fit=crop"
                        alt="Affiliate Marketing Dashboard" class="w-full rounded-lg shadow-lg">
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="bg-white dark:bg-gray-800 py-20">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl font-bold text-center mb-12">Why Choose LogicPulse?</h2>

                <div class="grid md:grid-cols-3 gap-8">
                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div
                            class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 17l4 4 4-4m0-5V3a2 2 0 00-2-2H6a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2v-7">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Advanced Tracking</h3>
                        <p class="text-gray-600 dark:text-gray-400">Monitor clicks, conversions, and commissions in
                            real-time for every campaign.</p>
                    </div>

                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div
                            class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a8 8 0 10-16 0v2h5"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Partner Network</h3>
                        <p class="text-gray-600 dark:text-gray-400">Access a thriving ecosystem of high-performing
                            affiliates and reputable brands.</p>
                    </div>

                    <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div
                            class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4H9m4-2h6m2 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Data-Driven Insights</h3>
                        <p class="text-gray-600 dark:text-gray-400">Leverage analytics to optimize campaigns and
                            maximize ROI with intuitive reports.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-3xl font-bold mb-4">Ready to Grow Your Revenue?</h2>
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">Join marketers worldwide maximizing profits
                    with LogicPulse's affiliate solutions.</p>
                <a href="#"
                    class="px-8 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition inline-block">Join
                    Now – It's Free</a>
            </div>
        </section>
    </main>

    <footer class="bg-gray-50 dark:bg-gray-800 py-12">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-gray-600 dark:text-gray-400">&copy; {{ date('Y') }} LogicPulse. All rights reserved.
                </div>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#"
                        class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Privacy
                        Policy</a>
                    <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Terms
                        of Service</a>
                    <a href="#"
                        class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Contact</a>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>