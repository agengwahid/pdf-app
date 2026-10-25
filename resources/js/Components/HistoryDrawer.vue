<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
    <!-- Backdrop -->
    <div
      class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity"
      @click="$emit('close')"
    ></div>

    <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
      <div class="w-screen max-w-md bg-[#111317] border-l border-[#232730] flex flex-col shadow-2xl">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-[#232730] flex items-center justify-between bg-[#15181e]">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
              <History class="w-4 h-4" />
            </div>
            <div>
              <h2 class="text-sm font-semibold tracking-wide text-zinc-100 uppercase">Riwayat Operasi</h2>
              <p class="text-xs text-zinc-400">Tersimpan di database PostgreSQL lokal</p>
            </div>
          </div>
          <button
            @click="$emit('close')"
            class="p-1.5 rounded-md text-zinc-400 hover:text-white hover:bg-[#20252e] transition"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Filter bar & Clear button -->
        <div class="px-6 py-3 border-b border-[#1f232b] flex items-center justify-between bg-[#13161c]">
          <span class="text-xs text-zinc-400 font-mono">
            {{ historyItems.length }} item tercatat
          </span>
          <button
            v-if="historyItems.length > 0"
            @click="clearAllHistory"
            :disabled="isClearing"
            class="text-xs text-rose-400 hover:text-rose-300 font-medium hover:underline flex items-center gap-1"
          >
            <Trash2 class="w-3 h-3" />
            Bersihkan Semua
          </button>
        </div>

        <!-- History List -->
        <div class="flex-1 overflow-y-auto p-6 space-y-3">
          <div
            v-if="historyItems.length === 0"
            class="h-64 flex flex-col items-center justify-center text-center text-zinc-500"
          >
            <FileClock class="w-10 h-10 mb-2 opacity-40" />
            <p class="text-sm font-medium">Belum ada riwayat</p>
            <p class="text-xs text-zinc-600 mt-1">Hasil penggabungan, kompresi, atau pembagian PDF akan tampil di sini.</p>
          </div>

          <div
            v-for="item in historyItems"
            :key="item.id"
            class="bg-[#171a21] border border-[#232833] rounded-lg p-4 hover:border-[#353c4d] transition group"
          >
            <div class="flex items-start justify-between gap-2 mb-2">
              <span
                :class="getActionBadgeClass(item.action)"
                class="px-2 py-0.5 rounded text-[10px] font-mono font-medium uppercase tracking-wider border"
              >
                {{ item.action }}
              </span>
              <span class="text-[11px] text-zinc-500 font-mono">
                {{ formatDateTime(item.created_at) }}
              </span>
            </div>

            <p class="text-xs font-semibold text-zinc-200 truncate mb-1" :title="item.result_name">
              {{ item.result_name }}
            </p>
            <p class="text-[11px] text-zinc-400 truncate mb-3" :title="item.original_name">
              Sumber: {{ item.original_name }}
            </p>

            <!-- Stats info -->
            <div class="flex items-center justify-between text-[11px] font-mono text-zinc-400 pt-2 border-t border-[#202530]">
              <div class="flex items-center gap-2">
                <span>{{ item.formatted_result_size }}</span>
                <span
                  v-if="item.action === 'compress' && item.reduction_percentage"
                  class="text-emerald-400 font-medium"
                >
                  -{{ item.reduction_percentage }}%
                </span>
                <span v-if="item.page_count" class="text-zinc-500">
                  ({{ item.page_count }} hal)
                </span>
              </div>

              <div class="flex items-center gap-2">
                <a
                  :href="item.download_url"
                  download
                  class="px-2 py-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 rounded text-xs font-sans flex items-center gap-1 transition"
                  title="Unduh file"
                >
                  <Download class="w-3 h-3" />
                  Unduh
                </a>
                <button
                  @click="deleteItem(item.id)"
                  class="p-1 text-zinc-500 hover:text-rose-400 rounded transition"
                  title="Hapus riwayat ini"
                >
                  <Trash2 class="w-3 h-3" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { History, X, Trash2, Download, FileClock } from 'lucide-vue-next';
import { formatDateTime } from '@/Utils/format';

const props = defineProps({
  isOpen: Boolean,
  historyItems: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close', 'refresh']);

const isClearing = ref(false);

function getActionBadgeClass(action) {
  switch (action) {
    case 'merge':
      return 'bg-blue-500/10 text-blue-400 border-blue-500/30';
    case 'compress':
      return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
    case 'split':
      return 'bg-purple-500/10 text-purple-400 border-purple-500/30';
    case 'manage':
      return 'bg-amber-500/10 text-amber-400 border-amber-500/30';
    case 'word_to_pdf':
      return 'bg-sky-500/10 text-sky-400 border-sky-500/30';
    default:
      return 'bg-zinc-500/10 text-zinc-400 border-zinc-500/30';
  }
}

async function deleteItem(id) {
  try {
    const res = await fetch(`/pdf/history/${id}`, {
      method: 'DELETE',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    if (res.ok) {
      emit('refresh');
    }
  } catch (err) {
    console.error('Failed to delete history item:', err);
  }
}

async function clearAllHistory() {
  if (!confirm('Yakin ingin menghapus seluruh riwayat dan file hasil lokal?')) return;
  isClearing.value = true;
  try {
    const res = await fetch('/pdf/history', {
      method: 'DELETE',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    if (res.ok) {
      emit('refresh');
    }
  } catch (err) {
    console.error('Failed to clear history:', err);
  } finally {
    isClearing.value = false;
  }
}
</script>
