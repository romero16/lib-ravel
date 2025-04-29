<script setup>
    import { ref, onMounted, computed, onBeforeUnmount, nextTick, watch, defineAsyncComponent, onUnmounted, onUpdated  } from 'vue';
    import EventBus from '@/EventBus.js';
    import breakpoints from '@/breakpoints.js';
    import debounce from 'lodash/debounce';
    import { useSearchStore } from '@/SearchStore.js';
    import { router, usePage } from '@inertiajs/vue3';


    const props = defineProps({
        show: {
            type: Boolean,
            default: false,
        },
        searchQuery:{
            type:String,
            default:""
        }
    });




    const modalInput = ref(false);
    const data = ref([]);
    const searchQuery = ref(props.searchQuery);
    const selectedIndex = ref(-1);
    const searchStore = useSearchStore();
    const selected = ref(false);
    let lastScrollTop = ref(0);
    const scrollList = ref(null);

    onMounted(() => {
        nextTick(() => {
            if (scrollList.value) {
                scrollList.value.addEventListener('scroll', handleScroll, { passive: true });
            }
        });
    });
    onUnmounted(() => {
        nextTick(() => {
            if (scrollList.value) {
                scrollList.value.removeEventListener('scroll', handleScroll);
            }
        });
        
    })

    watch(() => props.show, (newValue) => {
        if (newValue) {
            nextTick(() => {
                modalInput.value.focus()
            });
        }
    });


    const handleScroll = () => {
        if (props.show){
            const currentScroll = scrollList.value.scrollY || scrollList.value.scrollTop;
        if (!modalInput.value) return;

        if (currentScroll > lastScrollTop.value) {
            // Scroll hacia abajo
            modalInput.value.blur();
        } else if (currentScroll < lastScrollTop.value) {
            // Scroll hacia arriba
            modalInput.value.focus();
        }

        lastScrollTop.value = currentScroll <= 0 ? 0 : currentScroll; // Evita scroll negativo
        }

    };
    watch(searchQuery, () => {
        // if(searchQuery.value.length >= 2){
            let exist = data.value.some((word)=>word.word == searchQuery.value);
            if(exist && selected.value){
                clearfilteredWords(searchQuery.value)
            }else{
                fetchKeywords();
            }
        // }else{
        //     clearfilteredWords(searchQuery.value)
        // }

    });
    watch(data, (newVal) => {
        nextTick(() => {
            if (scrollList.value && newVal.length > 0) {
                scrollList.value.addEventListener('scroll', handleScroll, { passive: true });
            }
        });
    });

    const fetchKeywords = debounce(async () => {
        if (!searchQuery.value) {
            clearfilteredWords();
            return;
        }
        try {
            const response = await axios.get(route('equipments.keyword', { word: searchQuery.value }));
            if (!searchQuery.value){
                clearfilteredWords()
            }else{
                data.value = response.data;
                if (selectedIndex.value >= data.value.length) {
                    selectedIndex.value = data.value.length - 1;
                }
            }

        } catch (err) {
            console.error('Error al buscar palabras clave:', err.message);
        }
    }, 300);

    const emit = defineEmits(['close']);
    const closeModal = () => {
        document.body.style.overflow = 'auto'
        document.getElementsByTagName('html')[0].style.overflow = "auto";
        emit('close',searchQuery.value)
    };

    const classFormContent = computed(() => {
        if(inVerticalForm.value){
            return ' w-full';
        }else{
            return '';
        }
    });
    const inVerticalForm = computed(() => {
        return breakpoints.is == 'xs' || breakpoints.is == 'sm' || breakpoints.is == 'md';
    });

    const selectedWordMobile = (word) =>{
        const data = { by: 'search', q:  word  };
        searchStore.saveToLocalStorage(data);
        router.get(route('search',data),{
            onError: () => {
                disabled.value = false;
            }
        });
    }

    const replaceWord = (word) =>{
        searchQuery.value = word;
        modalInput.value?.focus()
    }
    const navigateSuggestions = (event) => {
        let selectedItem = 0;

        switch (event.key) {
            case 'Enter':
                selectedWordMobile(searchQuery.value);
                event.preventDefault();
                break;
            default:
                break;
        }
        // Hacer scroll al elemento seleccionado
        if (selectedItem) {
            selectedItem.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest' // Asegura que el elemento se vea sin forzar el scroll
            });
        }
    };

    const openSearchBar = () => {
            nextTick(() => {
                modalInput.value?.focus()
                document.getElementsByTagName('html')[0].style.overflow = "hidden";
                document.body.style.overflow = 'hidden';

            })

    };

    const clearfilteredWords = (word = '') =>{
        searchQuery.value = word;
        data.value = [];
        selectedIndex.value = -1;
    }






</script>

<template>
    <Teleport to="body">
    <div v-if="show" class="modal fixed min-h-screen inset-0 top-0 left-0 right-0 bottom-0 bg-gray-900 bg-opacity-100 z-1050 flex justify-center items-start" @click.self="closeModal">
        <div class="w-full p-4 bg-white flex flex-col min-h-screen">
            <form :action="route('search')" method="GET" @submit.prevent="selectedWordMobile(searchQuery)" :class="classFormContent">
                <div class="flex items-center">
                    <button @click="closeModal" class="text-gray-500 hover:text-gray-700 focus:outline-none mr-3 mt-4">
                        <i class="fa fa-arrow-left"></i>
                    </button>
                    <input id="input-field2" type="text"
                        :class="inVerticalForm ? 'rounded-t-lg' : 'rounded-l-lg'"
                        class="text-gray-500 sm-text peer w-3/5 px-3 pt-2 mt-5 pb-2 border border-gray-300 focus:outline-none focus:ring-0 focus:ring-gray-100 focus:border-transparent"
                        name="q"
                        ref="modalInput"
                        v-model="searchQuery"
                        autocomplete="off"
                        data-lpignore="true"
                        @keydown.enter.prevent="navigateSuggestions"
                        @focus="openSearchBar"
                    >
                    <button @click="toggleSearch"  class="absolute right-0 mt-5 mr-8 text-gray-500 hover:text-gray-700 focus:outline-none">
                        <i class="fa fa-search"></i>
                    </button>
                    <label for="input-field2"
                        :class="{
                            'top-[5px] text-xs text-gray-500': searchQuery,
                            'text-sm text-gray-500 peer-placeholder-shown:top-1/2  peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-[0px] peer-focus:text-xs': true
                        }"
                        class="absolute placeholder transition-all duration-200">
                            <span class="inset-y-0 left-0 flex items-center text-gray-500">

                                &nbsp;
                                {{ $t('Search in Enkky') }}
                            </span>
                    </label>
                </div>
                <ul v-if="data.length > 0" ref="scrollList" class="mt-4 lista h-[75vh] overflow-y-auto bg-white divide-y divide-gray-200">
                    <li v-for="(word, index) in data"
                        :key="word.id || index"
                        :class="[
                            'relative flex items-center justify-between gap-3 px-10 py-2 cursor-pointer transition-all duration-200',
                            index === selectedIndex ? 'bg-blue-100' : 'hover:bg-gray-100'
                        ]"
                        @click="selectedWordMobile(word.word)">


                        <div
                            class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 hover:text-gray-700 cursor-pointer"
                        >
                        <i class="fa fa-search"></i>
                        </div>

                        <span>{{ word.word }}</span>

                        <div @click.stop="replaceWord(word.word)"
                            class="absolute replace top-1/2 right-4 -translate-y-1/2 z-10 text-gray-500 hover:text-gray-700 cursor-pointer">
                            <i class="mdi mdi-arrow-top-left text-xl"></i>
                        </div>
                    </li>
                </ul>

            </form>

        </div>
    </div>
    </Teleport>
</template>
<style scoped>
    .list-ul {
  margin-top: 0px !important;
}

.modal{
    padding-top: 0px !important;
    height: 100vh !important;
}
@media (max-width: 768px) {
  .mobile {
    margin-left: -16px;
  }

  .fixed {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1050;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 10px;
  }

  input[type="text"] {
    width: 100%;
    padding: 1.5rem;
    font-size: 1.5rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    margin-top: 10px;
    font-size: 14px;
  }

  .mobile-search-bar {
    display: block;
  }

  .w-full {
    width: 100%;
  }
  .placeholder{
    margin-left: 40px;
    margin-top: 20px;
  }
  .replace{
    padding: 10px;
  }

}
</style>
