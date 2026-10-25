<template>
  <div class="min-h-screen bg-[#0c0d10] text-[#e3e6ec] flex flex-col font-sans selection:bg-amber-500/20 selection:text-amber-200">
    <!-- Top Bar -->
    <header class="border-b border-[#1c2028] bg-[#101217]/90 backdrop-blur sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Brand / Identity -->
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-amber-500 flex items-center justify-center text-black shadow-lg shadow-amber-500/20 font-black">
            <Layers class="w-5 h-5" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-extrabold tracking-tight text-base text-zinc-100">PDF Studio</span>
              <span class="px-1.5 py-0.2 rounded bg-[#1f242e] border border-[#2d3544] text-[10px] font-mono text-zinc-400">
                v1.0 Local
              </span>
            </div>
            <p class="text-[11px] text-zinc-400 font-mono hidden sm:block">
              Suite Manipulasi PDF Offline &bull; Laravel 13 &bull; PostgreSQL
            </p>
          </div>
        </div>

        <!-- Right Side: Engine Badge & History Button -->
        <div class="flex items-center gap-3">
          <!-- Offline Shield Badge -->
          <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[#141820] border border-[#222836] text-xs font-mono text-zinc-300">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>100% Offline / Tanpa Cloud</span>
          </div>

          <!-- History Drawer Toggle Button -->
          <button
            @click="isHistoryOpen = true"
            class="px-3.5 py-1.5 bg-[#171a22] hover:bg-[#202530] text-zinc-200 hover:text-white rounded-lg border border-[#282e3b] text-xs font-mono flex items-center gap-2 transition active:scale-95"
            title="Buka riwayat operasi"
          >
            <History class="w-3.5 h-3.5 text-amber-400" />
            <span>Riwayat</span>
            <span
              v-if="historyList.length > 0"
              class="px-1.5 py-0.2 rounded-full bg-amber-500 text-black text-[10px] font-bold"
            >
              {{ historyList.length }}
            </span>
          </button>
        </div>
      </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      <!-- Welcome Hero / Privacy Assurance -->
      <div class="bg-gradient-to-r from-[#12151c] via-[#151922] to-[#12151c] border border-[#202532] rounded-2xl p-6 sm:p-8 relative overflow-hidden shadow-2xl">
        <div class="max-w-2xl">
          <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-mono mb-3">
            <ShieldCheck class="w-3.5 h-3.5" />
            <span>Privasi Penuh: Dokumen tidak pernah meninggalkan komputer lokal Anda.</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-100">
            Pemrosesan Dokumen PDF Cepat & Mandiri
          </h1>
          <p class="text-sm text-zinc-400 mt-2 leading-relaxed">
            Gabungkan file, kurangi ukuran berkas (kompresi), ekstrak rentang halaman, serta atur urutan dan orientasi halaman secara visual menggunakan engine lokal Poppler dan Ghostscript berkecepatan tinggi.
          </p>
        </div>
      </div>

      <!-- Navigation Tabs (Anti-AI-Slop Clean Segmented Bar) -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-2 bg-[#101217] p-1.5 rounded-xl border border-[#1e232e]">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'flex items-center justify-center gap-2.5 py-3 px-4 rounded-lg text-xs font-semibold tracking-wide transition select-none',
            activeTab === tab.id
              ? 'bg-[#1b202a] text-amber-400 border border-[#2e3747] shadow-lg shadow-black/40'
              : 'text-zinc-400 hover:text-zinc-200 hover:bg-[#14171e]'
          ]"
        >
          <component :is="tab.icon" class="w-4 h-4 shrink-0" />
          <div class="text-left">
            <span class="block font-bold">{{ tab.title }}</span>
          </div>
        </button>
      </div>

      <!-- Active Tab Panel -->
      <section class="min-h-[460px]">
        <MergeTab
          v-if="activeTab === 'merge'"
          @operation-completed="handleOperationCompleted"
        />

        <CompressTab
          v-else-if="activeTab === 'compress'"
          @operation-completed="handleOperationCompleted"
        />

        <SplitTab
          v-else-if="activeTab === 'split'"
          @operation-completed="handleOperationCompleted"
        />

        <ManageTab
          v-else-if="activeTab === 'manage'"
          @operation-completed="handleOperationCompleted"
        />
      </section>
    </main>

    <!-- Utilitarian Technical Footer -->
    <footer class="border-t border-[#1a1e27] bg-[#0f1115] py-6 text-xs text-zinc-500 font-mono">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <span class="text-zinc-400 font-semibold">PDF Studio</span>
          <span>&bull;</span>
          <span>PHP 8.3 &bull; Laravel 13 &bull; PostgreSQL &bull; Ghostscript 10.02 &bull; Poppler</span>
        </div>
        <div class="flex items-center gap-4 text-zinc-400">
          <span>Penyimpanan Lokal: storage/app/pdf_studio</span>
        </div>
      </div>
    </footer>

    <!-- History Slide-over Drawer -->
    <HistoryDrawer
      :is-open="isHistoryOpen"
      :history-items="historyList"
      @close="isHistoryOpen = false"
      @refresh="fetchHistory"
    />

    <!-- Toast Notification -->
    <div
      v-if="toast"
      class="fixed bottom-6 right-6 z-50 max-w-md bg-[#161a22] border border-amber-500/40 text-zinc-200 p-4 rounded-xl shadow-2xl flex items-center justify-between gap-3 animate-in fade-in slide-in-from-bottom-3"
    >
      <div class="flex items-center gap-3 min-w-0">
        <CheckCircle2 class="w-5 h-5 text-amber-400 shrink-0" />
        <p class="text-xs font-medium truncate">{{ toast.message }}</p>
      </div>
      <button @click="toast = null" class="text-zinc-500 hover:text-white p-1">
        <X class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import {
  Merge,
  Minimize2,
  Split,
  Layers,
  History,
  ShieldCheck,
  CheckCircle2,
  X
} from 'lucide-vue-next';

import MergeTab from '@/Components/MergeTab.vue';
import CompressTab from '@/Components/CompressTab.vue';
import SplitTab from '@/Components/SplitTab.vue';
import ManageTab from '@/Components/ManageTab.vue';
import HistoryDrawer from '@/Components/HistoryDrawer.vue';

const props = defineProps({
  initialTab: {
    type: String,
    default: 'merge',
  },
  history: {
    type: Array,
    default: () => [],
  },
});

const activeTab = ref(props.initialTab || 'merge');
const historyList = ref([...props.history]);
const isHistoryOpen = ref(false);
const toast = ref(null);

const tabs = [
  { id: 'merge', title: 'Merge PDF', icon: Merge, desc: 'Gabung beberapa file' },
  { id: 'compress', title: 'Compress PDF', icon: Minimize2, desc: 'Kecilkan ukuran file' },
  { id: 'split', title: 'Split PDF', icon: Split, desc: 'Pisah / ekstrak halaman' },
  { id: 'manage', title: 'Manage PDF', icon: Layers, desc: 'Reorder, rotate & delete' },
];

function handleOperationCompleted(historyItem) {
  if (historyItem) {
    historyList.value.unshift(historyItem);
  }
  showToast('Operasi berhasil! File siap diunduh.');
}

function showToast(msg) {
  toast.value = { message: msg };
  setTimeout(() => {
    toast.value = null;
  }, 4000);
}

async function fetchHistory() {
  try {
    // In Inertia, reload or fetch via a lightweight json request
    window.location.reload();
  } catch (err) {
    console.error('Failed to reload history:', err);
  }
}
</script>
