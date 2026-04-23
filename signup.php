<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up | Fleet Management System</title>
    <link
        rel="shortcut icon"
        href="assets/images/favicon.png"
        type="image/x-icon"
    />
    <link rel="stylesheet" href="assets/css/animate.css" />
    <link rel="stylesheet" href="./src/css/tailwind.css" />

    <!-- ==== WOW JS ==== -->
    <script src="assets/js/wow.min.js"></script>
    <script>
        new WOW().init();
    </script>
</head>

<body>
    <!-- ====== Navbar Section Start -->
    <div class="absolute top-0 left-0 z-40 flex items-center w-full bg-transparent ud-header" id="nav">
        <div class="container mx-auto px-4">
            <div class="relative flex items-center justify-between -mx-4">
                <div class="max-w-full px-4 w-60">
                    <a href="index.html" class="block w-full py-5 navbar-logo">
                        <img src="assets/images/logo/ths_Logo-removebg-preview.png" alt="logo" class="w-full dark:hidden" style="width: 100px;height: 60px;" />
                        <img src="assets/images/logo/ths_Logo-removebg-preview.png" alt="logo" class="hidden w-full dark:block" style="height: 60; width: 70px;" />
                    </a>
                </div>
                <div class="flex items-center justify-between w-full px-4">
                    <div>
                        <button id="navbarToggler" class="absolute right-4 top-1/2 block -translate-y-1/2 rounded-lg px-3 py-[6px] ring-primary focus:ring-2 lg:hidden">
                            <span class="relative my-[6px] block h-[2px] w-[30px] bg-dark dark:bg-white"></span>
                            <span class="relative my-[6px] block h-[2px] w-[30px] bg-dark dark:bg-white"></span>
                            <span class="relative my-[6px] block h-[2px] w-[30px] bg-dark dark:bg-white"></span>
                        </button>
                        <nav id="navbarCollapse" class="absolute right-4 top-full hidden w-full max-w-[250px] rounded-lg bg-white dark:bg-dark-2 py-5 shadow-lg lg:static lg:block lg:w-full lg:max-w-full lg:bg-transparent dark:lg:bg-transparent lg:py-0 lg:px-4 lg:shadow-none xl:px-6">
                            <ul class="blcok lg:flex 2xl:ml-20">
                                <li class="relative group"><a href="index.html" class="flex py-2 mx-8 text-base font-medium ud-menu-scroll text-dark dark:text-white group-hover:text-primary lg:mr-0 lg:inline-flex lg:py-6 lg:px-0" style="color: gray;">Home</a></li>
                                <li class="relative group"><a href="/#about" style="color: gray;" class="flex py-2 mx-8 text-base font-medium ud-menu-scroll text-dark dark:text-white group-hover:text-primary lg:mr-0 lg:ml-7 lg:inline-flex lg:py-6 lg:px-0 xl:ml-10">About</a></li>
                                <li class="relative group"><a href="/#pricing" style="color: gray;" class="flex py-2 mx-8 text-base font-medium ud-menu-scroll text-dark dark:text-white group-hover:text-primary lg:mr-0 lg:ml-7 lg:inline-flex lg:py-6 lg:px-0 xl:ml-10">Pricing</a></li>
                                <li class="relative group"><a href="/#team" style="color: gray;" class="flex py-2 mx-8 text-base font-medium ud-menu-scroll text-dark dark:text-white group-hover:text-primary lg:mr-0 lg:ml-7 lg:inline-flex lg:py-6 lg:px-0 xl:ml-10">Team</a></li>
                                <li class="relative group"><a href="/#contact" style="color: gray;" class="flex py-2 mx-8 text-base font-medium ud-menu-scroll text-dark dark:text-white group-hover:text-primary lg:mr-0 lg:ml-7 lg:inline-flex lg:py-6 lg:px-0 xl:ml-10">Contact</a></li>
                                <li class="relative submenu-item group">
                                    <a href="javascript:void(0)" style="color: gray;" class="relative flex items-center justify-between py-2 mx-8 text-base font-medium text-primary group-hover:text-primary lg:mr-0 lg:ml-8 lg:inline-flex lg:py-6 lg:pl-0 lg:pr-4 xl:ml-10">
                                        Pages
                                        <svg class="ml-2 fill-current" width="16" height="20" viewBox="0 0 16 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M7.99999 14.9C7.84999 14.9 7.72499 14.85 7.59999 14.75L1.84999 9.10005C1.62499 8.87505 1.62499 8.52505 1.84999 8.30005C2.07499 8.07505 2.42499 8.07505 2.64999 8.30005L7.99999 13.525L13.35 8.25005C13.575 8.02505 13.925 8.02505 14.15 8.25005C14.375 8.47505 14.375 8.82505 14.15 9.05005L8.39999 14.7C8.27499 14.825 8.14999 14.9 7.99999 14.9Z"/>
                                        </svg>
                                    </a>
                                    <div class="submenu relative top-full left-0 hidden w-[250px] rounded-xs bg-white dark:bg-dark-2 p-4 transition-[top] duration-300 group-hover:opacity-100 lg:invisible lg:absolute lg:top-[110%] lg:block lg:opacity-0 lg:shadow-lg lg:group-hover:visible lg:group-hover:top-full">
                                        <a href="about.html" class="block rounded-sm py-[10px] px-4 text-sm text-body-color dark:text-dark-6 hover:text-primary dark:hover:text-primary">About Page</a>
                                        <a href="pricing.html" class="block rounded-sm py-[10px] px-4 text-sm text-body-color dark:text-dark-6 hover:text-primary dark:hover:text-primary">Pricing Page</a>
                                        <a href="contact.html" class="block rounded-sm py-[10px] px-4 text-sm text-body-color dark:text-dark-6 hover:text-primary dark:hover:text-primary">Contact Page</a>
                                        <a href="signup.php" class="block rounded-sm py-[10px] px-4 text-sm text-primary hover:text-primary dark:hover:text-primary">Sign Up Page</a>
                                        <a href="signin.php" class="block rounded-sm py-[10px] px-4 text-sm text-body-color dark:text-dark-6 hover:text-primary dark:hover:text-primary">Sign In Page</a>
                                        <a href="404.html" class="block rounded-sm py-[10px] px-4 text-sm text-body-color dark:text-dark-6 hover:text-primary dark:hover:text-primary">404 Page</a>
                                    </div>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <div class="flex items-center justify-end pr-16 lg:pr-0">
                        <label for="themeSwitcher" class="inline-flex items-center cursor-pointer" aria-label="themeSwitcher" name="themeSwitcher">
                            <input type="checkbox" name="themeSwitcher" id="themeSwitcher" class="sr-only" />
                            <span class="block text-dark dark:hidden dark:text-white">
                                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M13.3125 1.50001C12.675 1.31251 12.0375 1.16251 11.3625 1.05001C10.875 0.975006 10.35 1.23751 10.1625 1.68751C9.93751 2.13751 10.05 2.70001 10.425 3.00001C13.0875 5.47501 14.0625 9.11251 12.975 12.525C11.775 16.3125 8.25001 18.975 4.16251 19.0875C3.63751 19.0875 3.22501 19.425 3.07501 19.9125C2.92501 20.4 3.15001 20.925 3.56251 21.1875C4.50001 21.75 5.43751 22.2 6.37501 22.5C7.46251 22.8375 8.58751 22.9875 9.71251 22.9875C11.625 22.9875 13.5 22.5 15.1875 21.5625C17.85 20.1 19.725 17.7375 20.55 14.8875C22.1625 9.26251 18.975 3.37501 13.3125 1.50001ZM18.9375 14.4C18.2625 16.8375 16.6125 18.825 14.4 20.0625C12.075 21.3375 9.41251 21.6 6.90001 20.85C6.63751 20.775 6.33751 20.6625 6.07501 20.55C10.05 19.7625 13.35 16.9125 14.5875 13.0125C15.675 9.56251 15 5.92501 12.7875 3.07501C17.5875 4.68751 20.2875 9.67501 18.9375 14.4Z"/>
                                </svg>
                            </span>
                            <span class="hidden text-dark">
                                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_2172_3070)">
                                        <path d="M12 6.89999C9.18752 6.89999 6.90002 9.18749 6.90002 12C6.90002 14.8125 9.18752 17.1 12 17.1C14.8125 17.1 17.1 14.8125 17.1 12C17.1 9.18749 14.8125 6.89999 12 6.89999ZM12 15.4125C10.125 15.4125 8.58752 13.875 8.58752 12C8.58752 10.125 10.125 8.58749 12 8.58749C13.875 8.58749 15.4125 10.125 15.4125 12C15.4125 13.875 13.875 15.4125 12 15.4125Z"/>
                                        <path d="M12 4.2375C12.45 4.2375 12.8625 3.8625 12.8625 3.375V1.5C12.8625 1.05 12.4875 0.637497 12 0.637497C11.55 0.637497 11.1375 1.0125 11.1375 1.5V3.4125C11.175 3.8625 11.55 4.2375 12 4.2375Z"/>
                                        <path d="M12 19.7625C11.55 19.7625 11.1375 20.1375 11.1375 20.625V22.5C11.1375 22.95 11.5125 23.3625 12 23.3625C12.45 23.3625 12.8625 22.9875 12.8625 22.5V20.5875C12.8625 20.1375 12.45 19.7625 12 19.7625Z"/>
                                        <path d="M18.1125 6.74999C18.3375 6.74999 18.5625 6.67499 18.7125 6.48749L19.9125 5.28749C20.25 4.94999 20.25 4.42499 19.9125 4.08749C19.575 3.74999 19.05 3.74999 18.7125 4.08749L17.5125 5.28749C17.175 5.62499 17.175 6.14999 17.5125 6.48749C17.6625 6.67499 17.8875 6.74999 18.1125 6.74999Z"/>
                                        <path d="M5.32501 17.5125L4.12501 18.675C3.78751 19.0125 3.78751 19.5375 4.12501 19.875C4.27501 20.025 4.50001 20.1375 4.72501 20.1375C4.95001 20.1375 5.17501 20.0625 5.32501 19.875L6.52501 18.675C6.86251 18.3375 6.86251 17.8125 6.52501 17.475C6.18751 17.175 5.62501 17.175 5.32501 17.5125Z"/>
                                        <path d="M22.5 11.175H20.5875C20.1375 11.175 19.725 11.55 19.725 12.0375C19.725 12.4875 20.1 12.9 20.5875 12.9H22.5C22.95 12.9 23.3625 12.525 23.3625 12.0375C23.3625 11.55 22.95 11.175 22.5 11.175Z"/>
                                        <path d="M4.23751 12C4.23751 11.55 3.86251 11.1375 3.37501 11.1375H1.50001C1.05001 11.1375 0.637512 11.5125 0.637512 12C0.637512 12.45 1.01251 12.8625 1.50001 12.8625H3.41251C3.86251 12.8625 4.23751 12.45 4.23751 12Z"/>
                                        <path d="M18.675 17.5125C18.3375 17.175 17.8125 17.175 17.475 17.5125C17.1375 17.85 17.1375 18.375 17.475 18.7125L18.675 19.9125C18.825 20.0625 19.05 20.175 19.275 20.175C19.5 20.175 19.725 20.1 19.875 19.9125C20.2125 19.575 20.2125 19.05 19.875 18.7125L18.675 17.5125Z"/>
                                        <path d="M5.32501 4.125C4.98751 3.7875 4.46251 3.7875 4.12501 4.125C3.78751 4.4625 3.78751 4.9875 4.12501 5.325L5.32501 6.525C5.47501 6.675 5.70001 6.7875 5.92501 6.7875C6.15001 6.7875 6.37501 6.7125 6.52501 6.525C6.86251 6.1875 6.86251 5.6625 6.52501 5.325L5.32501 4.125Z"/>
                                    </g>
                                    <defs><clipPath id="clip0_2172_3070"><rect width="24" height="24" fill="white" /></clipPath></defs>
                                </svg>
                            </span>
                        </label>
                        <div class="hidden sm:flex">
                            <a href="signin.php" class="loginBtn py-2 px-[22px] text-base font-medium hover:opacity-70">Sign In</a>
                            <a href="signup.php" class="px-6 py-2 text-base font-medium text-white duration-300 ease-in-out rounded-md signUpBtn bg-primary hover:bg-blue-dark">Sign Up</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ====== Navbar Section End -->
    
    <div style="width: 100%; height: 90px; background-color: white;"></div>

    <!-- ====== Forms Section Start -->
    <section class="bg-[#F4F7FF] py-14 lg:py-[90px] dark:bg-dark">
        <div class="container mx-auto px-4">
            <div class="flex flex-wrap -mx-4">
                <div class="w-full px-4">
                    <div class="wow fadeInUp relative mx-auto max-w-[525px] overflow-hidden rounded-xl shadow-form bg-white dark:bg-dark-2 py-14 px-8 text-center sm:px-12 md:px-[60px]" data-wow-delay=".15s">
                        <div class="mb-10 text-center">
                            <a href="javascript:void(0)" class="mx-auto inline-block max-w-[160px]">
                                <img src="assets/images/logo/ths_Logo-removebg-preview.png" alt="logo" class="dark:hidden" />
                                <img src="assets/images/logo/mainlogo.png" alt="logo" class="hidden dark:block" />
                            </a>
                        </div>

                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Create an Account</h2>
                        <p class="text-gray-500 dark:text-gray-400 mb-8">Choose how you want to get started</p>

                        <!-- Choice Cards -->
                        <div class="space-y-4 mb-8">
                            <!-- Start a Company Card -->
                            <div onclick="window.location.href='register_company.php'" class="border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 cursor-pointer hover:border-primary transition-all duration-300 group">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center group-hover:bg-primary transition-colors duration-300">
                                        <i class="ri-building-line text-2xl text-blue-600 dark:text-blue-400 group-hover:text-white"></i>
                                    </div>
                                    <div class="text-left flex-1">
                                        <h3 class="font-semibold text-gray-800 dark:text-white text-lg">Start a Company</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Create your own fleet management company and start tracking</p>
                                        <div class="flex gap-3 mt-2 text-xs text-gray-400">
                                            <span>✓ Real time tracking</span>
                                            <span>✓ Manage fleet</span>
                                            <span>✓ Invite team</span>
                                        </div>
                                    </div>
                                    <i class="ri-arrow-right-s-line text-2xl text-gray-400 group-hover:text-primary group-hover:translate-x-1 transition-all"></i>
                                </div>
                            </div>

                            <!-- Join a Company Card -->
                            <div onclick="showInviteForm()" class="border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 cursor-pointer hover:border-primary transition-all duration-300 group">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center group-hover:bg-primary transition-colors duration-300">
                                        <i class="ri-user-add-line text-2xl text-green-600 dark:text-green-400 group-hover:text-white"></i>
                                    </div>
                                    <div class="text-left flex-1">
                                        <h3 class="font-semibold text-gray-800 dark:text-white text-lg">Join a Company</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">You've been invited to join an existing company</p>
                                        <div class="flex gap-3 mt-2 text-xs text-gray-400">
                                            <span>✓ Enter invite code</span>
                                            <span>✓ Join team</span>
                                            <span>✓ Start working</span>
                                        </div>
                                    </div>
                                    <i class="ri-arrow-right-s-line text-2xl text-gray-400 group-hover:text-primary group-hover:translate-x-1 transition-all"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Invite Code Form (Hidden by default) -->
                        <div id="inviteForm" style="display: none;" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Enter your invitation code to join your company</p>
                            <div class="flex gap-3">
                                <input type="text" id="inviteToken" placeholder="Enter invitation code" class="flex-1 px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary dark:bg-dark-3 dark:border-dark-3">
                                <button onclick="joinCompany()" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Join →</button>
                            </div>
                        </div>

                        <!-- OR Divider -->
                        <div class="relative my-8">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-white dark:bg-dark-2 text-gray-500">or</span>
                            </div>
                        </div>

                        <!-- Regular Sign In Link -->
                        <p class="text-base text-body-secondary">
                            Already have an account?
                            <a href="signin.php" class="text-primary hover:underline font-medium">
                                Sign In
                            </a>
                        </p>

                        <div>
                            <span class="absolute top-1 right-1">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="1.39737" cy="38.6026" r="1.39737" transform="rotate(-90 1.39737 38.6026)" fill="#3056D3"/>
                                    <circle cx="1.39737" cy="1.99122" r="1.39737" transform="rotate(-90 1.39737 1.99122)" fill="#3056D3"/>
                                    <circle cx="13.6943" cy="38.6026" r="1.39737" transform="rotate(-90 13.6943 38.6026)" fill="#3056D3"/>
                                    <circle cx="13.6943" cy="1.99122" r="1.39737" transform="rotate(-90 13.6943 1.99122)" fill="#3056D3"/>
                                    <circle cx="25.9911" cy="38.6026" r="1.39737" transform="rotate(-90 25.9911 38.6026)" fill="#3056D3"/>
                                    <circle cx="25.9911" cy="1.99122" r="1.39737" transform="rotate(-90 25.9911 1.99122)" fill="#3056D3"/>
                                    <circle cx="38.288" cy="38.6026" r="1.39737" transform="rotate(-90 38.288 38.6026)" fill="#3056D3"/>
                                    <circle cx="38.288" cy="1.99122" r="1.39737" transform="rotate(-90 38.288 1.99122)" fill="#3056D3"/>
                                    <circle cx="1.39737" cy="26.3057" r="1.39737" transform="rotate(-90 1.39737 26.3057)" fill="#3056D3"/>
                                    <circle cx="13.6943" cy="26.3057" r="1.39737" transform="rotate(-90 13.6943 26.3057)" fill="#3056D3"/>
                                    <circle cx="25.9911" cy="26.3057" r="1.39737" transform="rotate(-90 25.9911 26.3057)" fill="#3056D3"/>
                                    <circle cx="38.288" cy="26.3057" r="1.39737" transform="rotate(-90 38.288 26.3057)" fill="#3056D3"/>
                                    <circle cx="1.39737" cy="14.0086" r="1.39737" transform="rotate(-90 1.39737 14.0086)" fill="#3056D3"/>
                                    <circle cx="13.6943" cy="14.0086" r="1.39737" transform="rotate(-90 13.6943 14.0086)" fill="#3056D3"/>
                                    <circle cx="25.9911" cy="14.0086" r="1.39737" transform="rotate(-90 25.9911 14.0086)" fill="#3056D3"/>
                                    <circle cx="38.288" cy="14.0086" r="1.39737" transform="rotate(-90 38.288 14.0086)" fill="#3056D3"/>
                                </svg>
                            </span>
                            <span class="absolute left-1 bottom-1">
                                <svg width="29" height="40" viewBox="0 0 29 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="2.288" cy="25.9912" r="1.39737" transform="rotate(-90 2.288 25.9912)" fill="#3056D3"/>
                                    <circle cx="14.5849" cy="25.9911" r="1.39737" transform="rotate(-90 14.5849 25.9911)" fill="#3056D3"/>
                                    <circle cx="26.7216" cy="25.9911" r="1.39737" transform="rotate(-90 26.7216 25.9911)" fill="#3056D3"/>
                                    <circle cx="2.288" cy="13.6944" r="1.39737" transform="rotate(-90 2.288 13.6944)" fill="#3056D3"/>
                                    <circle cx="14.5849" cy="13.6943" r="1.39737" transform="rotate(-90 14.5849 13.6943)" fill="#3056D3"/>
                                    <circle cx="26.7216" cy="13.6943" r="1.39737" transform="rotate(-90 26.7216 13.6943)" fill="#3056D3"/>
                                    <circle cx="2.288" cy="38.0087" r="1.39737" transform="rotate(-90 2.288 38.0087)" fill="#3056D3"/>
                                    <circle cx="2.288" cy="1.39739" r="1.39737" transform="rotate(-90 2.288 1.39739)" fill="#3056D3"/>
                                    <circle cx="14.5849" cy="38.0089" r="1.39737" transform="rotate(-90 14.5849 38.0089)" fill="#3056D3"/>
                                    <circle cx="26.7216" cy="38.0089" r="1.39737" transform="rotate(-90 26.7216 38.0089)" fill="#3056D3"/>
                                    <circle cx="14.5849" cy="1.39761" r="1.39737" transform="rotate(-90 14.5849 1.39761)" fill="#3056D3"/>
                                    <circle cx="26.7216" cy="1.39761" r="1.39737" transform="rotate(-90 26.7216 1.39761)" fill="#3056D3"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ====== Forms Section End -->

    <!-- ====== Footer Section Start -->
    <footer class="relative z-10 bg-[#090E34] pt-20 lg:pt-[100px] text-center">
        <div class="container px-4 mx-auto">
            <div class="flex flex-wrap justify-center -mx-4">
                <div class="w-full px-4 sm:w-1/2 md:w-1/2 lg:w-4/12 xl:w-3/12">
                    <div class="w-full mb-10">
                        <a href="javascript:void(0)" class="mb-6 inline-block max-w-[160px]">
                            <img src="assets/images/logo/mainlogo.png" alt="logo" class="max-w-full" />
                        </a>
                        <p class="mb-8 max-w-[270px] text-base text-gray-7">We Transform manual work to optimize digital systems by using technology.</p>
                        <div class="flex items-center justify-center -mx-3">
                            <a href="javascript:void(0)" class="px-3 text-gray-7 hover:text-white"><svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" class="fill-current"><path d="M16.294 8.86875H14.369H13.6815V8.18125V6.05V5.3625H14.369H15.8128C16.1909 5.3625 16.5003 5.0875 16.5003 4.675V1.03125C16.5003 0.653125 16.2253 0.34375 15.8128 0.34375H13.3034C10.5878 0.34375 8.69714 2.26875 8.69714 5.12187V8.1125V8.8H8.00964H5.67214C5.19089 8.8 4.74402 9.17812 4.74402 9.72812V12.2031C4.74402 12.6844 5.12214 13.1313 5.67214 13.1313H7.94089H8.62839V13.8188V20.7281C8.62839 21.2094 9.00652 21.6562 9.55652 21.6562H12.7878C12.994 21.6562 13.1659 21.5531 13.3034 21.4156C13.4409 21.2781 13.544 21.0375 13.544 20.8312V13.8531V13.1656H14.2659H15.8128C16.2596 13.1656 16.6034 12.8906 16.6721 12.4781V12.4438V12.4094L17.1534 10.0375C17.1878 9.79688 17.1534 9.52187 16.9471 9.24687C16.8784 9.075 16.569 8.90312 16.294 8.86875Z"/></svg></a>
                            <a href="javascript:void(0)" class="px-3 text-gray-7 hover:text-white"><svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" class="fill-current"><path d="M20.1236 5.91236C20.2461 5.76952 20.0863 5.58286 19.905 5.64972C19.5004 5.79896 19.1306 5.8974 18.5837 5.95817C19.2564 5.58362 19.5693 5.04828 19.8237 4.39259C19.885 4.23443 19.7 4.09092 19.5406 4.16647C18.8931 4.47345 18.1945 4.70121 17.4599 4.83578C16.7338 4.11617 15.6988 3.6665 14.5539 3.6665C12.3554 3.6665 10.5725 5.32454 10.5725 7.36908C10.5725 7.65933 10.6081 7.94206 10.6752 8.21276C7.51486 8.06551 4.6968 6.71359 2.73896 4.64056C2.60477 4.49848 2.36128 4.51734 2.27772 4.69063C2.05482 5.15296 1.93056 5.66584 1.93056 6.20582C1.93056 7.49014 2.6332 8.62331 3.70132 9.28732C3.22241 9.27293 2.76441 9.17961 2.34234 9.02125C2.13684 8.94416 1.90127 9.07964 1.92888 9.28686C2.14084 10.8781 3.42915 12.1909 5.09205 12.5011C4.75811 12.586 4.40639 12.6311 4.04253 12.6311C3.95431 12.6311 3.86685 12.6284 3.78019 12.6231C3.55967 12.6094 3.38044 12.8067 3.47499 12.9954C4.09879 14.2404 5.44575 15.1096 7.0132 15.1367C5.65077 16.13 3.93418 16.7218 2.06882 16.7218C1.83882 16.7218 1.74015 17.0175 1.9442 17.1178C3.52016 17.8924 5.31487 18.3332 7.22182 18.3332C14.545 18.3332 18.549 12.6914 18.549 7.79843C18.549 7.63827 18.545 7.47811 18.5377 7.31945C19.1321 6.92012 19.6664 6.44528 20.1236 5.91236Z"/></svg></a>
                            <a href="javascript:void(0)" class="px-3 text-gray-7 hover:text-white"><svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" class="fill-current"><path d="M11.0297 14.4305C12.9241 14.4305 14.4598 12.8948 14.4598 11.0004C14.4598 9.10602 12.9241 7.57031 11.0297 7.57031C9.13529 7.57031 7.59958 9.10602 7.59958 11.0004C7.59958 12.8948 9.13529 14.4305 11.0297 14.4305Z"/><path d="M14.7554 1.8335H7.24463C4.25807 1.8335 1.83334 4.25823 1.83334 7.24479V14.6964C1.83334 17.7421 4.25807 20.1668 7.24463 20.1668H14.6962C17.7419 20.1668 20.1667 17.7421 20.1667 14.7555V7.24479C20.1667 4.25823 17.7419 1.8335 14.7554 1.8335ZM11.0296 15.4948C8.51614 15.4948 6.53496 13.4545 6.53496 11.0002C6.53496 8.54586 8.54571 6.50554 11.0296 6.50554C13.4839 6.50554 15.4946 8.54586 15.4946 11.0002C15.4946 13.4545 13.5134 15.4948 11.0296 15.4948ZM17.2393 6.91952C16.9436 7.24479 16.5 7.42221 15.9973 7.42221C15.5538 7.42221 15.1102 7.24479 14.7554 6.91952C14.4301 6.59425 14.2527 6.18027 14.2527 5.67758C14.2527 5.17489 14.4301 4.79049 14.7554 4.43565C15.0807 4.08081 15.4946 3.90339 15.9973 3.90339C16.4409 3.90339 16.914 4.08081 17.2393 4.40608C17.535 4.79049 17.7419 5.23403 17.7419 5.70715C17.7124 6.18027 17.535 6.59425 17.2393 6.91952Z"/><path d="M16.0276 4.96777C15.6432 4.96777 15.318 5.29304 15.318 5.67745C15.318 6.06186 15.6432 6.38713 16.0276 6.38713C16.412 6.38713 16.7373 6.06186 16.7373 5.67745C16.7373 5.29304 16.4416 4.96777 16.0276 4.96777Z"/></svg></a>
                            <a href="javascript:void(0)" class="px-3 text-gray-7 hover:text-white"><svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" class="fill-current"><path d="M18.8065 1.8335H3.16399C2.42474 1.8335 1.83334 2.42489 1.83334 3.16414V18.8362C1.83334 19.5459 2.42474 20.1668 3.16399 20.1668H18.7473C19.4866 20.1668 20.078 19.5754 20.078 18.8362V3.13457C20.1371 2.42489 19.5457 1.8335 18.8065 1.8335ZM7.24464 17.4168H4.55379V8.69371H7.24464V17.4168ZM5.88443 7.48135C4.99733 7.48135 4.31721 6.77167 4.31721 5.91414C4.31721 5.05661 5.0269 4.34694 5.88443 4.34694C6.74196 4.34694 7.45163 5.05661 7.45163 5.91414C7.45163 6.77167 6.8011 7.48135 5.88443 7.48135ZM17.4463 17.4168H14.7554V13.1883C14.7554 12.183 14.7258 10.8523 13.336 10.8523C11.9167 10.8523 11.7097 11.976 11.7097 13.0996V17.4168H9.01884V8.69371H11.6506V9.90608H11.6801C12.0645 9.1964 12.9221 8.48672 14.2527 8.48672C17.0027 8.48672 17.5054 10.2609 17.5054 12.6856V17.4168H17.4463Z"/></svg></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-12 border-t border-[#8890A4]/40 py-8 lg:mt-[60px]">
            <div class="container px-4 mx-auto">
                <div class="flex flex-wrap -mx-4">
                    <div class="w-full px-4 md:w-2/3 lg:w-1/2">
                        <div class="my-1">
                            <div class="flex items-center justify-center -mx-3 md:justify-start">
                                <a href="javascript:void(0)" class="px-3 text-base text-gray-7 hover:text-white hover:underline">Privacy policy</a>
                                <a href="javascript:void(0)" class="px-3 text-base text-gray-7 hover:text-white hover:underline">Legal notice</a>
                                <a href="javascript:void(0)" class="px-3 text-base text-gray-7 hover:text-white hover:underline">Terms of service</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- ====== Footer Section End -->

    <!-- ====== Back To Top Start -->
    <a href="javascript:void(0)" class="fixed left-auto items-center justify-center hidden w-10 h-10 text-white transition duration-300 ease-in-out rounded-md shadow-md back-to-top bottom-8 right-8 z-999 bg-primary hover:bg-dark">
        <span class="mt-[6px] h-3 w-3 rotate-45 border-t border-l border-white"></span>
    </a>
    <!-- ====== Back To Top End -->

    <script src="assets/js/main.js"></script>
    <script>
        function showInviteForm() {
            const form = document.getElementById('inviteForm');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
                form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                form.style.display = 'none';
            }
        }

        function joinCompany() {
            const token = document.getElementById('inviteToken').value.trim();
            if (token) {
                window.location.href = `register_invite.php?token=${token}`;
            } else {
                alert('Please enter your invitation code');
                document.getElementById('inviteToken').focus();
            }
        }

        // Close invite form when clicking outside (optional)
        document.addEventListener('click', function(event) {
            const inviteForm = document.getElementById('inviteForm');
            const joinCard = event.target.closest('.border-2');
            
            if (inviteForm && inviteForm.style.display === 'block') {
                if (!joinCard || !joinCard.textContent.includes('Join a Company')) {
                    // Don't auto-close, let user manually close or submit
                }
            }
        });
    </script>
</body>
</html>