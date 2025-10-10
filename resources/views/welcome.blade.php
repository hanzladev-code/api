<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>LogicPulse - Affiliate Marketing Revolutionized</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */
        </style>
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Day/Night: Save user's theme preference and listen for toggling.
        document.addEventListener('DOMContentLoaded', function () {
            const html = document.documentElement;
            const storedTheme = localStorage.getItem('theme');
            const systemPreference = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            function setTheme(theme) {
                if (theme === 'dark') {
                    html.classList.add('dark');
                } else {
                    html.classList.remove('dark');
                }
                localStorage.setItem('theme', theme);
            }
            setTheme(storedTheme || systemPreference);

            document.getElementById('theme-toggle').onclick = function () {
                const current = html.classList.contains('dark') ? 'dark' : 'light';
                setTheme(current === 'dark' ? 'light' : 'dark');
                // Animate toggle icon
                document.getElementById('theme-icon-sun').classList.toggle('hidden');
                document.getElementById('theme-icon-moon').classList.toggle('hidden');
            }
        });
    </script>
</head>

<body
    class="bg-gradient-to-br from-gray-50 via-blue-50 to-gray-100 dark:from-gray-900 dark:via-gray-800 dark:to-slate-900 text-gray-900 dark:text-white min-h-screen font-sans selection:bg-blue-200 dark:selection:bg-blue-600">
    <header class="fixed w-full bg-white/80 dark:bg-gray-900/80 shadow-lg backdrop-blur z-30 transition-colors">
        <nav class="container mx-auto px-6 py-4 flex items-center justify-between relative">
            <div class="flex items-center space-x-3">
                <img src="https://em-content.zobj.net/source/microsoft/310/electric-light-bulb_1f4a1.png" alt="Logo"
                    class="w-8 h-8">
                <span class="text-2xl font-black tracking-tight text-blue-600 dark:text-blue-400">LogicPulse</span>
            </div>
            <ul class="hidden md:flex space-x-8">
                <li><a href="#features"
                        class="font-semibold hover:text-blue-500 dark:hover:text-blue-300 transition">Features</a></li>
                <li><a href="#testimonials"
                        class="font-semibold hover:text-blue-500 dark:hover:text-blue-300 transition">Testimonials</a>
                </li>
                <li><a href="#pricing"
                        class="font-semibold hover:text-blue-500 dark:hover:text-blue-300 transition">Pricing</a></li>
                <li><a href="#contact"
                        class="font-semibold hover:text-blue-500 dark:hover:text-blue-300 transition">Contact</a></li>
            </ul>
            <div class="flex items-center space-x-3">
                <button id="theme-toggle" aria-label="Switch dark/light mode"
                    class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                    <svg id="theme-icon-sun" xmlns="http://www.w3.org/2000/svg"
                        class="w-6 h-6 text-yellow-400 dark:hidden block" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <circle cx="12" cy="12" r="5" />
                        <path
                            d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M16.36 16.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M16.36 7.64l1.42-1.42" />
                    </svg>
                    <svg id="theme-icon-moon" xmlns="http://www.w3.org/2000/svg"
                        class="w-6 h-6 text-gray-600 dark:text-blue-200 hidden dark:block" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z" />
                    </svg>
                </button>
                <a href="#"
                    class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow transition hidden md:inline-block">Join</a>
            </div>
        </nav>
    </header>

    <main class="pt-28 md:pt-32 pb-12 space-y-24">
        <!-- Hero Section -->
        <section class="relative container mx-auto px-6 py-12 flex flex-col-reverse lg:flex-row items-center gap-14">
            <div class="lg:w-1/2">
                <div
                    class="inline-flex items-center px-3 py-1 bg-blue-100 dark:bg-blue-900 rounded-full text-blue-600 dark:text-blue-200 text-xs font-bold mb-4 shadow">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4H9m4-2h6m2 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Revolutionize your affiliate marketing!
                </div>
                <h1 class="text-4xl md:text-5xl font-black mb-6 leading-tight">
                    Power Up Your Earnings<br>
                    <span class="text-blue-600 dark:text-blue-400">With Smarter Affiliate Marketing</span>
                </h1>
                <p class="text-lg text-gray-700 dark:text-gray-300 mb-8 max-w-xl">
                    LogicPulse empowers affiliates and brands to connect, track, and optimize campaigns for unparalleled
                    success. Data-driven, transparent, and scalable.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="#"
                        class="px-7 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-lg font-bold shadow-lg">Get
                        Started</a>
                    <a href="#features"
                        class="px-7 py-3 border-2 border-blue-600 dark:border-blue-400 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-950 transition text-lg font-bold">Learn
                        More</a>
                </div>
                <ul class="flex space-x-6 mt-8">
                    <li class="flex items-center text-gray-500 dark:text-gray-400 text-sm"><svg
                            class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <circle cx="10" cy="10" r="5" />
                        </svg> Trusted by 5,000+ affiliates</li>
                    <li class="flex items-center text-gray-500 dark:text-gray-400 text-sm"><svg
                            class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <rect width="14" height="14" x="3" y="3" rx="2" />
                        </svg> Brand safe solutions</li>
                </ul>
            </div>
            <div class="lg:w-1/2 flex justify-center">
                <div class="relative">
                    <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800&h=600&fit=crop"
                        alt="Affiliate Marketing Dashboard"
                        class="w-full max-w-md rounded-xl shadow-2xl ring-4 ring-blue-100 dark:ring-blue-900">
                    <div
                        class="absolute -bottom-7 left-1/2 -translate-x-1/2 bg-white dark:bg-gray-800 px-6 py-3 rounded-full shadow-lg flex items-center space-x-2 text-xs font-semibold text-gray-700 dark:text-gray-200 border dark:border-gray-900/50">
                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927C9.469 2.213 10.531 2.213 10.951 2.927l.857 1.485c.392.68 1.34.854 1.989.346l1.3-1.042c.657-.527 1.642.054 1.463.825l-.382 1.585c-.178.741.39 1.463 1.144 1.463h1.679c.818 0 1.15 1.048.507 1.516l-1.349 1.007c-.666.496-.666 1.489 0 1.986l1.349 1.007c.644.478.311 1.516-.507 1.516h-1.679c-.754 0-1.322.722-1.144 1.463l.382 1.585c.179.771-.806 1.352-1.463.825l-1.3-1.042c-.649-.508-1.597-.334-1.989.346l-.857 1.485c-.42.714-1.482.714-1.902 0l-.857-1.485c-.392-.68-1.34-.854-1.989-.346l-1.3 1.042c-.657.527-1.642-.054-1.463-.825l.382-1.585c.178-.741-.39-1.463-1.144-1.463H2.055c-.818 0-1.15-1.048-.507-1.516l1.349-1.007c.666-.496.666-1.489 0-1.986L1.548 8.143c-.644-.478-.311-1.516.507-1.516h1.679c.754 0 1.322-.722 1.144-1.463l-.382-1.585c-.179-.771.806-1.352 1.463-.825l1.3 1.042c.649.508 1.597.334 1.989-.346l.857-1.485z" />
                        </svg>
                        Voted #1 Affiliate Dashboard 2024
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features"
            class="bg-gradient-to-br from-white to-blue-50 dark:from-gray-800 dark:to-blue-950 py-20 rounded-3xl shadow-inner mx-2 md:mx-0">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl font-bold text-center mb-4">Why Choose LogicPulse?</h2>
                <p class="max-w-2xl text-lg text-gray-600 dark:text-gray-300 mx-auto text-center mb-12">
                    Our all-in-one affiliate platform gives you the tools to succeed—from robust analytics to a thriving
                    partner network and secure payments.
                </p>

                <div class="grid md:grid-cols-3 gap-8">
                    <div class="p-8 bg-white dark:bg-gray-800 rounded-2xl shadow hover:shadow-xl transition">
                        <div
                            class="w-14 h-14 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-5">
                            <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 17l4 4 4-4m0-5V3a2 2 0 00-2-2H6a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2v-7">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Advanced Tracking</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">Monitor clicks, conversions, and commissions in
                            real-time for every campaign.</p>
                        <ul class="list-disc pl-5 text-sm text-gray-500 dark:text-gray-400">
                            <li>End-to-end campaign insights</li>
                            <li>Automated fraud detection</li>
                            <li>Real-time dashboard analytics</li>
                        </ul>
                    </div>

                    <div class="p-8 bg-white dark:bg-gray-800 rounded-2xl shadow hover:shadow-xl transition">
                        <div
                            class="w-14 h-14 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-5">
                            <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a8 8 0 10-16 0v2h5"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Partner Network</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">Access a thriving ecosystem of high-performing
                            affiliates and reputable brands.</p>
                        <ul class="list-disc pl-5 text-sm text-gray-500 dark:text-gray-400">
                            <li>24/7 partner support</li>
                            <li>Tailored collaboration tools</li>
                            <li>Brand & influencer matchmaking</li>
                        </ul>
                    </div>

                    <div class="p-8 bg-white dark:bg-gray-800 rounded-2xl shadow hover:shadow-xl transition">
                        <div
                            class="w-14 h-14 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-5">
                            <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4H9m4-2h6m2 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Data-Driven Insights</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">Leverage analytics to optimize campaigns and
                            maximize ROI with intuitive reports.</p>
                        <ul class="list-disc pl-5 text-sm text-gray-500 dark:text-gray-400">
                            <li>Automated reporting</li>
                            <li>Custom dashboards</li>
                            <li>Revenue maximization tips</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section id="testimonials" class="container mx-auto px-6 py-20">
            <h2 class="text-3xl font-bold text-center mb-4">What Our Partners Say</h2>
            <p class="max-w-xl mx-auto text-center text-lg mb-12 text-gray-600 dark:text-gray-300">See how LogicPulse
                empowers both affiliates and brands around the globe.</p>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow hover:shadow-xl transition flex flex-col">
                    <div class="flex items-center mb-4">
                        <img src="https://randomuser.me/api/portraits/men/68.jpg" alt="User"
                            class="w-12 h-12 rounded-full mr-3">
                        <div>
                            <div class="font-bold text-lg">Amit S.</div>
                            <div class="text-blue-500 text-xs">Premium Affiliate</div>
                        </div>
                    </div>
                    <p class="flex-1 text-gray-700 dark:text-gray-300">"LogicPulse's intuitive tracking lets me focus on
                        growing my audience and revenue. The dashboards are a game changer."</p>
                    <span class="block mt-6 text-yellow-400 text-xl">★★★★★</span>
                </div>
                <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow hover:shadow-xl transition flex flex-col">
                    <div class="flex items-center mb-4">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="User"
                            class="w-12 h-12 rounded-full mr-3">
                        <div>
                            <div class="font-bold text-lg">Rebecca C.</div>
                            <div class="text-blue-500 text-xs">Brand Partner</div>
                        </div>
                    </div>
                    <p class="flex-1 text-gray-700 dark:text-gray-300">"Their network helped us double conversions and
                        made collaborating with new affiliates seamless and trustworthy."</p>
                    <span class="block mt-6 text-yellow-400 text-xl">★★★★★</span>
                </div>
                <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow hover:shadow-xl transition flex flex-col">
                    <div class="flex items-center mb-4">
                        <img src="https://randomuser.me/api/portraits/men/26.jpg" alt="User"
                            class="w-12 h-12 rounded-full mr-3">
                        <div>
                            <div class="font-bold text-lg">Phil T.</div>
                            <div class="text-blue-500 text-xs">Data Analyst</div>
                        </div>
                    </div>
                    <p class="flex-1 text-gray-700 dark:text-gray-300">"I love the real-time analytics and the highly
                        responsive support. LogicPulse is now central to our growth."</p>
                    <span class="block mt-6 text-yellow-400 text-xl">★★★★★</span>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing"
            class="bg-gradient-to-br from-blue-50 to-white dark:from-blue-950 dark:to-gray-900 py-20 rounded-3xl shadow-inner mx-2 md:mx-0">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl font-bold text-center mb-8">Pricing Plans</h2>
                <div class="flex flex-col md:flex-row gap-10 justify-center">
                    <div
                        class="flex-1 bg-white dark:bg-gray-800 p-8 rounded-2xl shadow hover:shadow-xl border border-blue-100 dark:border-gray-900 transition">
                        <span class="block text-blue-600 dark:text-blue-400 font-bold mb-2">Starter</span>
                        <div class="text-4xl font-black mb-4">$0 <span
                                class="text-base font-normal text-gray-400">/mo</span></div>
                        <ul class="mb-6 space-y-2 text-gray-600 dark:text-gray-300 text-sm">
                            <li>Up to 500 Clicks</li>
                            <li>Community Support</li>
                            <li>Core Analytics</li>
                        </ul>
                        <a href="#"
                            class="inline-block px-6 py-3 text-white bg-blue-600 rounded-lg hover:bg-blue-700 font-semibold shadow">Try
                            Free</a>
                    </div>
                    <div
                        class="flex-1 bg-blue-600/80 dark:bg-blue-900 p-8 rounded-2xl shadow-xl border-2 border-blue-600 dark:border-blue-400 text-white scale-105">
                        <span class="block font-bold mb-2 uppercase tracking-wide">Professional</span>
                        <div class="text-4xl font-black mb-4">$49 <span
                                class="text-base font-normal text-blue-100">/mo</span></div>
                        <ul class="mb-6 space-y-2 text-blue-100 text-sm">
                            <li>Unlimited Campaigns & Clicks</li>
                            <li>Advanced Analytics</li>
                            <li>Premium Support</li>
                            <li>Automated Payments</li>
                        </ul>
                        <a href="#"
                            class="inline-block px-6 py-3 text-blue-600 dark:text-blue-400 bg-white dark:bg-gray-800 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-800 font-bold shadow">Start
                            Now</a>
                    </div>
                    <div
                        class="flex-1 bg-white dark:bg-gray-800 p-8 rounded-2xl shadow hover:shadow-xl border border-blue-100 dark:border-gray-900 transition">
                        <span class="block text-blue-600 dark:text-blue-400 font-bold mb-2">Enterprise</span>
                        <div class="text-4xl font-black mb-4">Custom</div>
                        <ul class="mb-6 space-y-2 text-gray-600 dark:text-gray-300 text-sm">
                            <li>Dedicated Manager</li>
                            <li>API Integration</li>
                            <li>Bespoke Analytics & Security</li>
                        </ul>
                        <a href="#"
                            class="inline-block px-6 py-3 text-white bg-blue-600 rounded-lg hover:bg-blue-700 font-semibold shadow">Contact
                            Sales</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section
            class="py-20 bg-gradient-to-br from-blue-600 to-blue-400 dark:from-blue-900 dark:to-blue-700 text-white rounded-3xl mx-2 md:mx-0 shadow-lg">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-3xl font-bold mb-4">Ready to Grow Your Revenue?</h2>
                <p class="text-lg mb-8">Join marketers worldwide maximizing profits with LogicPulse's affiliate
                    solutions. <br />Sign up for free or talk to our specialists to learn more!</p>
                <a href="#"
                    class="px-10 py-4 bg-white text-blue-700 rounded-full hover:bg-blue-100 hover:text-blue-900 transition font-bold text-lg inline-block shadow-md">Get
                    Started Free</a>
            </div>
        </section>
    </main>

    <footer
        class="bg-gradient-to-tr from-gray-100 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-16 mt-16 border-t border-blue-100 dark:border-gray-800">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-8 md:space-y-0">
                <div class="text-gray-600 dark:text-gray-400 text-center md:text-left">&copy; {{ date('Y') }} <span
                        class="text-blue-600 dark:text-blue-400 font-semibold">LogicPulse</span>. All rights reserved.
                </div>
                <ul class="flex flex-wrap gap-6 justify-center md:justify-end">
                    <li>
                        <a href="#"
                            class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 font-semibold transition">Privacy
                            Policy</a>
                    </li>
                    <li>
                        <a href="#"
                            class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 font-semibold transition">Terms
                            of Service</a>
                    </li>
                    <li>
                        <a href="#contact"
                            class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 font-semibold transition">Contact</a>
                    </li>
                    <li>
                        <a href="mailto:hello@logicpulse.com"
                            class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 font-semibold transition underline">hello@logicpulse.com</a>
                    </li>
                </ul>
            </div>
            <div class="mt-10 flex justify-center space-x-6">
                <a href="#" aria-label="Twitter" class="hover:text-blue-400 transition"><svg
                        class="w-6 h-6 text-blue-400 fill-current" viewBox="0 0 24 24">
                        <path
                            d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z" />
                    </svg></a>
                <a href="#" aria-label="LinkedIn" class="hover:text-blue-600 transition"><svg
                        class="w-6 h-6 text-blue-600 fill-current" viewBox="0 0 24 24">
                        <path
                            d="M4.983 3.5C3.327 3.5 2 4.845 2 6.483c0 1.614 1.299 2.958 2.996 2.958H8c-.092 0-.184-.01-.277-.018.059.005.119.018.183.018h.032c1.647 0 2.956-1.367 2.956-2.96C11.894 4.845 10.656 3.5 9 3.5h-.016C8.827 3.5 8.655 3.5 8.482 3.5H4.983zm.017 14.5c-1.656 0-3-1.33-3-2.972v-8.28c0-1.63 1.309-2.96 2.956-2.96C8.655 3.788 10 5.114 10 6.84v8.28c0 1.642-1.336 2.972-3 2.972H5zm5-11.5a.5.5 0 01.5.5v13c0 .276-.224.5-.5.5a.499.499 0 01-.5-.5v-13a.5.5 0 01.5-.5z" />
                    </svg></a>
                <a href="#" aria-label="Facebook" class="hover:text-blue-700 transition"><svg
                        class="w-6 h-6 text-blue-700 fill-current" viewBox="0 0 24 24">
                        <path
                            d="M22.675 0H1.325C.593 0 0 .592 0 1.326v21.348C0 23.408.593 24 1.326 24h11.495v-9.294H9.692V11.01h3.129V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.462.099 2.797.143v3.24l-1.918.001c-1.504 0-1.796.715-1.796 1.763v2.313h3.587l-.467 3.696h-3.12L16.82 24h5.354C23.408 24 24 23.408 24 22.674V1.326C24 .592 23.408 0 22.675 0" />
                    </svg></a>
            </div>
            <div class="mt-10 text-center text-xs text-gray-400">Crafted with <span class="text-pink-600">♥</span> by
                the LogicPulse Team.</div>
        </div>
    </footer>
</body>

</html>