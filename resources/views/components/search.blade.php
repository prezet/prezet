<!-- Command Palette -->
<!-- An Alpine.js and Tailwind CSS component by https://pinemix.com -->
<!-- Alpine.js focus plugin is required, for more info http://pinemix.com/docs/getting-started -->
<div
    x-data="{
  // Customize Command Palette
  open: false,
  resetOnOpen: true,
  closeOnSelection: true,

  // Add your custom functionality or navigation when an option is selected
  optionSelected() {
    // console.log(this.highlightedOption);
  },

  // Available options (id and label are required)
  options: [
    {
      id: 1,
      label: 'New file',
      command: 'new-file',
      icon: '<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'1.5\' stroke=\'currentColor\' data-slot=\'icon\' class=\'hi-outline hi-document-plus inline-block size-6\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z\'/></svg>',
      shortcut: 'N',
    },
    {
      id: 2,
      label: 'New folder',
      command: 'new-folder',
      icon: '<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'1.5\' stroke=\'currentColor\' data-slot=\'icon\' class=\'hi-outline hi-folder-plus inline-block size-6\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z\'/></svg>',
      shortcut: 'F',
    },
    {
      id: 3,
      label: 'New project',
      command: 'new-project',
      icon: '<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'1.5\' stroke=\'currentColor\' data-slot=\'icon\' class=\'hi-outline hi-squares-plus inline-block size-6\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v2.25A2.25 2.25 0 0 0 6 10.5Zm0 9.75h2.25A2.25 2.25 0 0 0 10.5 18v-2.25a2.25 2.25 0 0 0-2.25-2.25H6a2.25 2.25 0 0 0-2.25 2.25V18A2.25 2.25 0 0 0 6 20.25Zm9.75-9.75H18a2.25 2.25 0 0 0 2.25-2.25V6A2.25 2.25 0 0 0 18 3.75h-2.25A2.25 2.25 0 0 0 13.5 6v2.25a2.25 2.25 0 0 0 2.25 2.25Z\'/></svg>',
      shortcut: 'P',
    },
    {
      id: 4,
      label: 'Archive project',
      command: 'archive-project',
      icon: '<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'1.5\' stroke=\'currentColor\' data-slot=\'icon\' class=\'hi-outline hi-archive-box inline-block size-6\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z\'/></svg>',
      shortcut: 'A',
    },
    {
      id: 5,
      label: 'Format code',
      command: 'format-code',
      icon: '<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'1.5\' stroke=\'currentColor\' data-slot=\'icon\' class=\'hi-outline hi-code-bracket-square inline-block size-6\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M14.25 9.75 16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z\'/></svg>',
      shortcut: 'Y',
    },
  ],

  // Helper variables
  modifierKey: '',
  filterTerm: '',
  filterResults: [],
  highlightedOption: null,
  highlightedIndex: -1,
  enableMouseHighlighting: true,

  // Initialization
  init() {
    if (this.open) {
      this.openCommandPalette();
    }

    // Initialize filter results array
    this.filterResults = this.options;

    // Set the modifier key based on platform
    this.modifierKey = /mac/i.test(navigator.userAgentData ? navigator.userAgentData.platform : navigator.platform) ? '⌘' : 'Ctrl';
  },

  // Open Command Palette
  openCommandPalette() {
    if (this.resetOnOpen) {
      this.filterTerm = '';
      this.highlightedOption = null;
      this.highlightedIndex = -1;
      this.filterResults = this.options;
    }

    this.open = true;

    $nextTick(() => {
      // Focus filter input
      $focus.focus($refs.elFilter);
    });
  },

  // Close Command Palette
  closeCommandPalette() {
    this.open = false;

    $nextTick(() => {
      // Focus toggle button
      $focus.focus($refs.elToggleButton);
    });
  },

  // Enable mouse interaction
  enableMouseInteraction() {
    this.enableMouseHighlighting = true;
  },

  // Filter functionality
  filter() {
    if (this.filterTerm === '') {
      this.filterResults = this.options;
    } else {
      this.filterResults = this.options.filter((option) => {
        return option.label.toLowerCase().includes(this.filterTerm.toLowerCase());
      });
    }

    // Refresh highlighted array index (the results have been updated)
    if (this.filterResults.length > 0 && this.highlightedOption) {
        this.highlightedIndex = this.filterResults.findIndex((option) => {
          return option.id === this.highlightedOption.id;
        });
      }
  },

  // Set an option as highlighted
  setHighlighted(id, mode) {
    if (id === null) {
      this.highlightedOption = null;
      this.highlightedIndex = -1;
    } else if (this.highlightedOption?.id != id && (mode === 'keyboard' || (mode === 'mouse' && this.enableMouseHighlighting))) {
      this.highlightedOption = this.options.find(options => options.id === id) || null;

      // Set highlighted index of filter results
      if (mode === 'mouse' && this.enableMouseHighlighting) {
          this.highlightedIndex = this.filterResults.findIndex((option) => {
            return option.id === id;
          });
      } else {
        // We are in keyboard mode, disable mouse navigation
        this.enableMouseHighlighting = false;

        // Scroll listbox to make the highlighted element visible
        $refs.elListbox.querySelector('li[data-id=\'' + id + '\']').scrollIntoView({ block: 'nearest' });
      }
    }
  },

  // Check if the given id is the highlighted one
  isHighlighted(id) {
    return id === this.highlightedOption?.id || false;
  },

  // Navigate results functionality
  navigateResults(mode) {
    if (this.filterResults.length > 0) {
      const maxIndex = this.filterResults.length - 1;

      if (mode === 'first') {
        this.highlightedIndex = 0;
      } else if (mode === 'last') {
        this.highlightedIndex = maxIndex;
      } else if (mode === 'previous') {
        if (this.highlightedIndex > 0 && this.highlightedIndex <= maxIndex) {
          this.highlightedIndex--;
        } else if (this.highlightedIndex === -1) {
          this.highlightedIndex = 0;
        }
      } else if (mode === 'next') {
        if (this.highlightedIndex >= 0 && this.highlightedIndex < maxIndex) {
          this.highlightedIndex++;
        } else if (this.highlightedIndex === -1) {
          this.highlightedIndex = 0;
        }
      }

      if (!this.filterResults[this.highlightedIndex]?.id) {
        this.highlightedIndex = 0;
      }

      this.setHighlighted(this.filterResults[this.highlightedIndex].id, 'keyboard');
    }
  },

  // On option selected
  onOptionSelected() {
    if (this.highlightedOption != null) {
      this.optionSelected();

      if (this.closeOnSelection) {
        this.closeCommandPalette();
      }
    }
  },
}"
    x-on:keydown.ctrl.k.prevent.document="openCommandPalette()"
    x-on:keydown.meta.k.prevent.document="openCommandPalette()"
>
    <!-- Toggle Button -->
    <button
        x-ref="elToggleButton"
        x-on:click="openCommandPalette()"
        type="button"
        class="group inline-flex min-w-64 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm/6 font-medium text-zinc-800 hover:border-zinc-300 hover:text-zinc-900 hover:shadow-sm focus:ring-zinc-300/25 active:border-zinc-200 active:shadow-none dark:border-zinc-700 dark:bg-transparent dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:text-zinc-200 dark:focus:ring-zinc-600/50 dark:active:border-zinc-700"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            data-slot="icon"
            class="hi-mini hi-magnifying-glass inline-block size-5 opacity-60 group-hover:text-zinc-600 group-hover:opacity-100 dark:group-hover:text-zinc-400"
        >
            <path
                fill-rule="evenodd"
                d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z"
                clip-rule="evenodd"
            />
        </svg>
        <span class="grow text-start opacity-60 group-hover:opacity-100">
    Search..
  </span>
        <span class="flex-none text-xs font-semibold opacity-75">
    <span x-text="modifierKey" class="opacity-75"></span>
    <span>K</span>
  </span>
    </button>
    <!-- END Toggle Button -->

    <!-- Backdrop -->
    <div
        x-cloak
        x-show="open"
        x-trap.inert.noscroll="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-bind:aria-hidden="!open"
        x-on:keydown.esc.prevent.stop="closeCommandPalette()"
        class="z-90 fixed inset-0 overflow-y-auto overflow-x-hidden bg-zinc-900/75 p-4 backdrop-blur-sm will-change-auto md:py-8 lg:px-8 lg:py-16"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
    >
        <!-- Command Palette Container -->
        <div
            x-cloak
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-32"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-32"
            x-on:click.outside="closeCommandPalette()"
            class="mx-auto flex w-full max-w-lg flex-col rounded-xl shadow-xl will-change-auto dark:text-zinc-100 dark:shadow-black/25"
            role="document"
        >
            <!-- Search Input -->
            <div class="relative rounded-t-lg bg-white px-2 pt-2 dark:bg-zinc-800">
                <div
                    class="flex w-full items-center rounded-lg bg-zinc-100 px-3 dark:bg-zinc-700/75"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        data-slot="icon"
                        class="hi-outline hi-command-line inline-block size-6 opacity-50"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m6.75 7.5 3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z"
                        />
                    </svg>
                    <input
                        x-ref="elFilter"
                        x-model="filterTerm"
                        x-on:input.debounce.50ms="filter($event)"
                        x-on:keydown.enter.prevent.stop="onOptionSelected()"
                        x-on:keydown.up.prevent.stop="navigateResults('previous')"
                        x-on:keydown.down.prevent.stop="navigateResults('next')"
                        x-on:keydown.home.prevent.stop="navigateResults('first')"
                        x-on:keydown.end.prevent.stop="navigateResults('last')"
                        x-on:keydown.page-up.prevent.stop="navigateResults('first')"
                        x-on:keydown.page-down.prevent.stop="navigateResults('last')"
                        type="text"
                        class="w-full border-none bg-transparent py-3 text-sm placeholder:text-zinc-500 focus:outline-none focus:ring-0 dark:placeholder:text-zinc-400"
                        placeholder="Search commands.."
                        tabindex="0"
                        role="combobox"
                        aria-expanded="true"
                        aria-autocomplete="list"
                    />
                </div>
            </div>
            <!-- EMD Search Input -->

            <!-- Listbox -->
            <ul
                x-show="filterResults.length > 0"
                x-ref="elListbox"
                x-on:mousemove.throttle="enableMouseInteraction()"
                x-on:mouseleave="setHighlighted(null)"
                class="max-h-72 overflow-auto rounded-b-xl bg-white p-2 dark:bg-zinc-800"
                role="listbox"
            >
                <template x-for="option in filterResults" :key="option.id">
                    <li
                        x-on:click="onOptionSelected()"
                        x-on:mouseenter="setHighlighted(option.id, 'mouse')"
                        x-bind:class="{
          'text-white bg-zinc-600 dark:text-white dark:bg-zinc-600': isHighlighted(option.id),
          'text-zinc-600 dark:text-zinc-300': ! isHighlighted(option.id),
        }"
                        x-bind:data-selected="isHighlighted(option.id)"
                        x-bind:data-id="option.id"
                        x-bind:data-label="option.label"
                        x-bind:aria-selected="isHighlighted(option.id)"
                        class="group flex cursor-pointer items-center justify-between gap-3 rounded-lg px-3 text-sm"
                        role="option"
                        tabindex="-1"
                    >
                        <div class="flex grow items-center gap-3 py-2">
                            <div x-html="option.icon" class="size-6 opacity-60"></div>
                            <div x-text="option.label" class="font-medium"></div>
                        </div>
                        <div class="flex-none text-xs font-semibold opacity-75">
                            <span x-text="modifierKey" class="opacity-75"></span> +
                            <span x-text="option.shortcut"></span>
                        </div>
                    </li>
                </template>
            </ul>
            <!-- END Listbox -->

            <!-- No Results Feedback -->
            <div
                x-show="filterResults.length === 0"
                class="rounded-b-xl bg-white p-3 dark:bg-zinc-800"
            >
                <div
                    class="space-y-3 py-1.5 text-center text-sm text-zinc-500 dark:text-zinc-400"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        data-slot="icon"
                        class="hi-outline hi-x-circle inline-block size-8 opacity-50"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                        />
                    </svg>
                    <p>No commands found</p>
                </div>
            </div>
            <!-- END No Results Feedback -->
        </div>
        <!-- END Command Palette Container -->
    </div>
    <!-- END Backdrop -->
</div>
<!-- END Command Palette -->
