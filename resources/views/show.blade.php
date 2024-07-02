<x-prezet::template>
    {{-- Mobile Sidebar --}}
    <div x-show="showSidebar">
        <div
            class="fixed inset-0 z-40 flex h-full items-start overflow-y-auto bg-stone-900/50 pr-10 backdrop-blur lg:hidden"
        >
            <div
                class="min-h-full w-full max-w-xs bg-white px-4 pb-12 pt-24 sm:px-6"
                x-on:click.outside="showSidebar = false"
            >
                <x-prezet::nav :nav="$nav" />
            </div>
        </div>
    </div>
    <div
        class="relative mx-auto flex w-full max-w-8xl flex-auto justify-center sm:px-2 lg:px-8 xl:px-12"
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
            <div class="absolute inset-y-0 right-0 w-[50vw] bg-stone-50"></div>
            <div
                class="absolute bottom-0 right-0 top-16 hidden h-12 w-px bg-gradient-to-t from-stone-800"
            ></div>
            <div
                class="absolute bottom-0 right-0 top-28 hidden w-px bg-stone-800"
            ></div>
            <div
                class="sticky top-[4.75rem] -ml-0.5 h-[calc(100vh-4.75rem)] w-64 overflow-y-auto overflow-x-hidden py-16 pl-0.5 pr-8 xl:w-72 xl:pr-16"
            >
                <x-prezet::nav :nav="$nav" />
            </div>
        </div>
        {{-- Main Content --}}
        <div
            class="min-w-0 max-w-2xl flex-auto px-4 py-16 lg:max-w-none lg:pl-8 lg:pr-0 xl:px-16"
        >
            <article>
                <header class="mb-9 space-y-1">
                    <p
                        class="font-display text-sm font-medium text-primary-500"
                    >
                        {{ $frontmatter->category }}
                    </p>
                    <h1
                        class="font-display text-4xl font-medium tracking-tight text-stone-900"
                    >
                        {{ $frontmatter->title }}
                    </h1>
                </header>
                <div
                    class="prose-headings:font-display prose prose-stone max-w-none prose-a:border-b prose-a:border-dashed prose-a:border-black/30 prose-a:font-semibold prose-a:no-underline hover:prose-a:border-solid prose-img:rounded"
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
                    class="font-display text-sm font-medium text-stone-900"
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
</x-prezet::template>
