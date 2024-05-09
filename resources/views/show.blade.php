<x-prezet::layout>
    <div
        class="max-w-8xl relative mx-auto flex w-full flex-auto justify-center sm:px-2 lg:px-8 xl:px-12"
        x-data="{
            activeHeading: null,
            init() {
                const headingElements = document.querySelectorAll(
                    'article h2, article h3',
                )

                // Create an Intersection Observer
                const observer = new IntersectionObserver(
                    (entries) => {
                        const visibleHeadings = entries.filter(
                            (entry) => entry.isIntersecting,
                        )
                        if (visibleHeadings.length > 0) {
                            // Find the visible heading with the lowest top value
                            const topHeading = visibleHeadings.reduce(
                                (prev, current) =>
                                    prev.boundingClientRect.top <
                                    current.boundingClientRect.top
                                        ? prev
                                        : current,
                            )

                            this.activeHeading = topHeading.target.textContent
                        }
                    },
                    { rootMargin: '0px 0px -75% 0px', threshold: 1 },
                )

                // Observe each heading
                headingElements.forEach((heading) => {
                    observer.observe(heading)
                })
            },

            scrollToHeading(headingId) {
                const heading = document.getElementById(headingId)
                if (heading) {
                    heading.scrollIntoView({ behavior: 'smooth' })
                }
            },
        }"
    >
        {{-- Left Sidebar --}}
        <div class="hidden lg:relative lg:block lg:flex-none">
            <div class="absolute inset-y-0 right-0 w-[50vw] bg-slate-50"></div>
            <div
                class="absolute bottom-0 right-0 top-16 hidden h-12 w-px bg-gradient-to-t from-slate-800"
            ></div>
            <div
                class="absolute bottom-0 right-0 top-28 hidden w-px bg-slate-800"
            ></div>
            <div
                class="sticky top-[4.75rem] -ml-0.5 h-[calc(100vh-4.75rem)] w-64 overflow-y-auto overflow-x-hidden py-16 pl-0.5 pr-8 xl:w-72 xl:pr-16"
            >
                <nav class="text-base lg:text-sm">
                    <ul role="list" class="space-y-9">
                        @foreach ($nav as $section)
                            <li>
                                <h2
                                    class="font-display font-medium text-slate-900"
                                >
                                    {{ $section['title'] }}
                                </h2>
                                <ul
                                    role="list"
                                    class="mt-2 space-y-2 border-l-2 border-slate-100 lg:mt-4 lg:space-y-4 lg:border-slate-200"
                                >
                                    @foreach ($section['links'] as $link)
                                        <li class="relative">
                                            <a
                                                @class([
                                                    'block w-full pl-3.5 before:pointer-events-none before:absolute before:-left-1 before:top-1/2 before:h-1.5 before:w-1.5 before:-translate-y-1/2 before:rounded-full',
                                                    'text-primary-500 before:bg-primary-500 font-semibold' =>
                                                        url()->current() === route('prezet.show', ['slug' => $link['slug']]),
                                                    'text-slate-500 before:hidden before:bg-slate-300 hover:text-slate-600 hover:before:block' =>
                                                        url()->current() !== route('prezet.show', ['slug' => $link['slug']]),
                                                ])
                                                href="{{ route('prezet.show', ['slug' => $link['slug']]) }}"
                                            >
                                                {{ $link['title'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>
        </div>

        {{-- Main Content --}}
        <div
            class="min-w-0 max-w-2xl flex-auto px-4 py-16 lg:max-w-none lg:pl-8 lg:pr-0 xl:px-16"
        >
            <a
                href="{{ route('prezet.index') }}"
                class="flex items-center text-sm text-slate-600"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="mr-2 h-4 w-4"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
                    />
                </svg>
                Articles
            </a>

            <article class="mt-12">
                <header class="mb-9 space-y-1">
                    <p
                        class="font-display text-primary-500 text-sm font-medium"
                    >
                        {{ $frontmatter->category }}
                    </p>
                    <h1
                        class="font-display text-4xl font-medium tracking-tight text-slate-900"
                    >
                        {{ $frontmatter->title }}
                    </h1>
                </header>
                <div
                    class="prose-headings:font-display prose prose-slate max-w-none prose-a:border-b prose-a:border-dashed prose-a:border-black/30 prose-a:font-semibold prose-a:no-underline hover:prose-a:border-solid prose-img:rounded"
                >
                    {!! $body !!}
                </div>
            </article>
        </div>

        {{-- Right Sidebar --}}
        <div
            class="hidden xl:sticky xl:top-[4.75rem] xl:-mr-6 xl:block xl:h-[calc(100vh-4.75rem)] xl:flex-none xl:overflow-y-auto xl:py-16 xl:pr-6"
        >
            <nav aria-labelledby="on-this-page-title" class="w-56">
                <h2
                    id="on-this-page-title"
                    class="font-display text-sm font-medium text-slate-900"
                >
                    On this page
                </h2>
                <ol role="list" class="mt-4 space-y-3 text-sm">
                    @foreach ($headings as $h2)
                        <li>
                            <h3>
                                <a
                                    href="#{{ $h2['id'] }}"
                                    :class="{'text-primary-500 hover:text-primary-500': activeHeading === '#{{ $h2['title'] }}'}"
                                    x-on:click.prevent="scrollToHeading('{{ $h2['id'] }}')"
                                    class="transition-colors"
                                >
                                    {{ $h2['title'] }}
                                </a>
                            </h3>

                            @if ($h2['children'])
                                <ol
                                    role="list"
                                    class="mt-2 space-y-3 border-l pl-5"
                                >
                                    @foreach ($h2['children'] as $h3)
                                        <li>
                                            <a
                                                href="#{{ $h3['id'] }}"
                                                :class="{'text-primary-500 hover:text-primary-500': activeHeading === '#{{ $h3['title'] }}'}"
                                                x-on:click.prevent="scrollToHeading('{{ $h3['id'] }}')"
                                                class="transition-colors"
                                            >
                                                {{ $h3['title'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        </div>
    </div>
</x-prezet::layout>
