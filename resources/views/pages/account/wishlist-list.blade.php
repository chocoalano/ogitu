@extends('layouts.app')

@section('content')
    @livewire('ecommerce.wishlist-list')
@endsection

{{-- Old Static Template Below --}}
@section('old-content')
    <section class="lg:py-10 py-6">
        <div class="container">
            <div class="border border-default-200 divide-y divide-default-200 rounded-lg overflow-hidden ">
                <div class="px-4 py-4 flex flex-wrap justify-between items-center">
                    <div class="md:w-1/2 w-auto">
                        <div class="flex items-center">
                            <img src="assets/images/dishes/sushi-roll.png" class="lg:h-28 lg:w-28 w-14 h-14 lg:me-4 me-2">
                            <div class="md:w-auto w-2/3">
                                <p class="text-xs font-medium text-primary mb-2">Japan Food</p>
                                <h4 class="text-xl font-semibold text-default-800 mb-2 line-clamp-1">Sushi Roll</h4>
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                        <span class="lg:hidden flex text-primary text-base font-semibold">4.2</span>
                                        <div class="lg:flex hidden gap-1.5">
                                            <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                            <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                            <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                            <i data-lucide="star" class="h-5 w-5 text-default-400 fill-default-400"></i>
                                        </div>
                                    </div>
                                    <h6 class="text-sm text-default-500 font-medium mt-1">(1.2k)</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:w-1/4 w-auto">
                        <div class="text-end">
                            <h4 class="text-2xl font-medium text-default-500">$15</h4>
                            <h4 class="text-base font-medium text-default-500 line-through">$25</h4>
                        </div>
                    </div>

                    <div class="md:w-auto w-full lg:mt-0 mt-4">
                        <div class="flex lg:flex-col justify-between gap-2">
                            <a href="javascript:void(0)"
                                class="py-3 px-6 font-medium text-center text-white bg-primary rounded-full hover:bg-primary-500 transition-all">Add
                                to Cart</a>
                            <a href="javascript:void(0)"
                                class="py-3 px-6 font-medium text-center lg:text-primary rounded-full lg:hover:bg-primary/20 lg:bg-transparent bg-primary text-white transition-all">Remove</a>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-4 flex flex-wrap justify-between items-center">
                    <div class="md:w-1/2 w-auto">
                        <div class="flex items-center">
                            <img src="assets/images/dishes/onion-rings.png" class="lg:h-28 lg:w-28 w-14 h-14 lg:me-4 me-2">
                            <div class="md:w-auto w-2/3">
                                <p class="text-xs font-medium text-primary mb-2">Indian Breakfast</p>
                                <h4 class="text-xl font-semibold text-default-800 mb-2 line-clamp-1">Onion Rings</h4>
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                        <span class="lg:hidden flex text-primary text-base font-semibold">4.2</span>
                                        <div class="lg:flex hidden gap-1.5">
                                            <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                            <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                            <i data-lucide="star" class="h-5 w-5 text-default-400 fill-default-400"></i>
                                            <i data-lucide="star" class="h-5 w-5 text-default-400 fill-default-400"></i>
                                        </div>
                                    </div>
                                    <h6 class="text-sm text-default-500 font-medium mt-1">(1.2k)</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:w-1/4 w-auto">
                        <div class="text-end">
                            <h4 class="text-2xl font-medium text-default-500">$25</h4>
                            <h4 class="text-base font-medium text-default-500 line-through">$32</h4>
                        </div>
                    </div>

                    <div class="md:w-auto w-full lg:mt-0 mt-4">
                        <div class="flex lg:flex-col justify-between gap-2">
                            <a href="javascript:void(0)"
                                class="py-3 px-6 font-medium text-center text-white bg-primary rounded-full hover:bg-primary-500 transition-all">Add
                                to Cart</a>
                            <a href="javascript:void(0)"
                                class="py-3 px-6 font-medium text-center lg:text-primary rounded-full lg:hover:bg-primary/20 lg:bg-transparent bg-primary text-white transition-all">Remove</a>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-4 flex flex-wrap justify-between items-center">
                    <div class="md:w-1/2 w-auto">
                        <div class="flex items-center">
                            <img src="assets/images/dishes/garlic-herb-bread.png"
                                class="lg:h-28 lg:w-28 w-14 h-14 lg:me-4 me-2">
                            <div class="md:w-auto w-2/3">
                                <p class="text-xs font-medium text-primary mb-2">Italian-American Dish</p>
                                <h4 class="text-xl font-semibold text-default-800 mb-2 line-clamp-1">Garlic Herb Bread</h4>
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                        <span class="lg:hidden flex text-primary text-base font-semibold">4.2</span>
                                        <div class="lg:flex hidden gap-1.5">
                                            <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                            <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                            <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                            <i data-lucide="star" class="h-5 w-5 text-default-400 fill-default-400"></i>
                                        </div>
                                    </div>
                                    <h6 class="text-sm text-default-500 font-medium mt-1">(1.2k)</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:w-1/4 w-auto">
                        <div class="text-end">
                            <h4 class="text-2xl font-medium text-default-500">$45</h4>
                            <h4 class="text-base font-medium text-default-500 line-through">$49</h4>
                        </div>
                    </div>

                    <div class="md:w-auto w-full lg:mt-0 mt-4">
                        <div class="flex lg:flex-col justify-between gap-2">
                            <a href="javascript:void(0)"
                                class="py-3 px-6 font-medium text-center text-white bg-primary rounded-full hover:bg-primary-500 transition-all">Add
                                to Cart</a>
                            <a href="javascript:void(0)"
                                class="py-3 px-6 font-medium text-center lg:text-primary rounded-full lg:hover:bg-primary/20 lg:bg-transparent bg-primary text-white transition-all">Remove</a>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-4 flex flex-wrap justify-between items-center">
                    <div class="md:w-1/2 w-auto">
                        <div class="flex items-center">
                            <img src="assets/images/dishes/palmier-puff-pastry.png"
                                class="lg:h-28 lg:w-28 w-14 h-14 lg:me-4 me-2">
                            <div class="md:w-auto w-2/3">
                                <p class="text-xs font-medium text-primary mb-2">France Desserts</p>
                                <h4 class="text-xl font-semibold text-default-800 mb-2 line-clamp-1">Palmier Puff Pastry
                                </h4>
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                        <span class="lg:hidden flex text-primary text-base font-semibold">4.2</span>
                                        <div class="lg:flex hidden gap-1.5">
                                            <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                            <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                            <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                            <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-yellow-400"></i>
                                        </div>
                                    </div>
                                    <h6 class="text-sm text-default-500 font-medium mt-1">(1.2k)</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:w-1/4 w-auto">
                        <div class="text-end">
                            <h4 class="text-2xl font-medium text-default-500">$85</h4>
                            <h4 class="text-base font-medium text-default-500 line-through">$99</h4>
                        </div>
                    </div>

                    <div class="md:w-auto w-full lg:mt-0 mt-4">
                        <div class="flex lg:flex-col justify-between gap-2">
                            <a href="javascript:void(0)"
                                class="py-3 px-6 font-medium text-center text-white bg-primary rounded-full hover:bg-primary-500 transition-all">Add
                                to Cart</a>
                            <a href="javascript:void(0)"
                                class="py-3 px-6 font-medium text-center lg:text-primary rounded-full lg:hover:bg-primary/20 lg:bg-transparent bg-primary text-white transition-all">Remove</a>
                        </div>
                    </div>
                </div>
            </div><!-- end grid-cols -->
        </div>
    </section>
@endsection
