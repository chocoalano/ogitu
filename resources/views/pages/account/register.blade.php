@extends('layouts.app')

@section('content')
    @livewire('auth.register')
@endsection

@section('old_content')
    <div
        class="relative md:h-screen sm:py-16 py-36 flex items-center bg-linear-to-b from-primary/5 via-primary/5 to-primary/10">
        <div class="container">
            <div class="flex justify-center items-center lg:max-w-lg">
                <div class="flex flex-col h-full">
                    <div class="shrink">
                        <div class="pb-10">
                            <a href="/" class="flex items-center">
                                <img src="https://sinergiabadisentosa.com/wp-content/uploads/2024/08/cropped-Pt-sinergi-abadi-sentosa-png-2.png"
                                    alt="logo" class="flex h-12 dark:hidden">
                                <img src="https://sinergiabadisentosa.com/wp-content/uploads/2024/08/cropped-Pt-sinergi-abadi-sentosa-png-2.png"
                                    alt="logo" class="hidden h-12 dark:flex">
                            </a>
                        </div>
                        <div class="">
                            <h1 class="text-3xl font-semibold text-default-800 mb-2">Register</h1>
                            <p class="text-sm text-default-500 max-w-md">Lorem ipsum dolor sit amet, consectetur adipiscing
                                elit, sed do eiusmod taempor.</p>
                        </div>
                        <div class="pt-16">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-default-900 mb-2" for="FullName">Full
                                    Name</label>
                                <input
                                    class="block w-full rounded-full py-2.5 px-4 bg-white border border-default-200 focus:ring-transparent focus:border-default-200 dark:bg-default-50"
                                    id="FullName" placeholder="Enter your Name" type="email">
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-default-900 mb-2"
                                    for="LoggingEmailAddress">Email</label>
                                <input
                                    class="block w-full rounded-full py-2.5 px-4 bg-white border border-default-200 focus:ring-transparent focus:border-default-200 dark:bg-default-50"
                                    id="LoggingEmailAddress" placeholder="Enter your email" type="email">
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-default-900 mb-2"
                                    for="form-password">Password</label>
                                <div class="flex" data-x-password>
                                    <input
                                        class="form-password block w-full rounded-s-full py-2.5 px-4 bg-white border border-default-200 focus:ring-transparent focus:border-default-200 dark:bg-default-50"
                                        id="form-password" placeholder="Enter your password" type="password">
                                    <button
                                        class="password-toggle inline-flex items-center justify-center py-2.5 px-4 border rounded-e-full bg-white -ms-px border-default-200 dark:bg-default-50"
                                        id="password-addon">
                                        <i class="password-eye-on h-5 w-5 text-default-600" data-lucide="eye"></i>
                                        <i class="password-eye-off h-5 w-5 text-default-600" data-lucide="eye-off"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex justify-center mb-6">
                                <a class="relative inline-flex items-center justify-center px-6 py-3 rounded-full text-base bg-primary text-white capitalize transition-all hover:bg-primary-500 w-full"
                                    href="home.html">
                                    Register
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="grow flex items-end justify-center mt-16">
                        <p class="text-default-700 text-center mt-auto">Already have an account ?<a
                                class="text-primary ms-1" href="auth-login.html"><b>Login</b></a></p>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="absolute top-1/2 -translate-y-1/3 start-0 end-0 w-full -z-10">
                <img class="w-full opacity-50 flex" src="https://res.cloudinary.com/dqta7pszj/image/upload/v1740024751/rclmcqugizwgji0550x4.png">
            </div>

            <div class="absolute top-0 end-0 hidden xl:flex h-5/6">
                <img class="w-full z-0" src="https://res.cloudinary.com/dqta7pszj/image/upload/v1740024751/rclmcqugizwgji0550x4.png">
            </div>
        </div>
    </div>
@endsection
