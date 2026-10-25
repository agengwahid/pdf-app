<template>
  <div class="space-y-6">
    <!-- Result State -->
    <div
      v-if="result"
      class="bg-[#141b18] border border-emerald-500/30 rounded-xl p-6 relative overflow-hidden"
    >
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
            <CheckCircle2 class="w-6 h-6" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-mono uppercase px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-semibold">
                Pemisahan Berhasil
              </span>
              <span class="text-xs text-zinc-400 font-mono">
                {{ formatBytes(result.result_size) }} &bull; {{ result.pages_extracted }} Halaman Diekstrak
              </span>
            </div>
            <h3 class="text-base font-semibold text-zinc-100 mt-1 font-mono truncate max-w-md">
              {{ result.result_name }}
            </h3>
            <p class="text-xs text-zinc-400 mt-0.5">
              {{ result.message }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <a
            :href="result.download_url"
            download
            class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-black font-semibold text-sm rounded-lg flex items-center gap-2 shadow-lg shadow-emerald-500/10 transition active:scale-95"
          >
            <Download class="w-4 h-4" />
            Unduh Hasil ({{ splitMode === 'zip' ? '.ZIP' : '.PDF' }})
          </a>
          <button
            @click="resetAll"
            class="px-4 py-2.5 bg-[#1c222b] hover:bg-[#252c38] text-zinc-300 text-sm font-medium rounded-lg border border-[#2d3443] transition"
          >
            Pisah Dokumen Lain
          </button>
        </div>
      </div>
    </div>

    <!-- Upload and Configuration State -->
    <div v-else class="space-y-6">
      <!-- Upload Zone -->
      <div
        v-if="!selectedFile"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        :class="[
          'relative border-2 border-dashed rounded-xl p-10 transition flex flex-col items-center justify-center text-center cursor-pointer',
          isDragging
            ? 'border-amber-500 bg-amber-500/5'
            : 'border-[#262b35] hover:border-[#3a4252] bg-[#121419]/70 hover:bg-[#15181e]'
        ]"
        @click="triggerFileInput"
      >
        <input
          ref="fileInputRef"
          type="file"
          accept="application/pdf"
          class="hidden"
          @change="handleFileInput"
        />

        <div class="w-14 h-14 rounded-2xl bg-[#191d24] border border-[#2a303c] flex items-center justify-center text-amber-400 mb-4 shadow-inner">
          <Split class="w-7 h-7" />
        </div>

        <h3 class="text-base font-semibold text-zinc-100">
          Pilih File PDF untuk Dipisahkan
        </h3>
        <p class="text-xs text-zinc-400 mt-1 max-w-sm">
          Tarik & lepas file ke sini untuk memilih halaman tertentu atau rentang halaman yang ingin diekstrak.
        </p>

        <div class="mt-4 flex items-center gap-2">
          <span class="px-3 py-1.5 rounded-lg bg-[#1f242e] border border-[#2c3340] text-xs font-mono text-zinc-300">
            Pilih Dokumen PDF
          </span>
          <span class="text-xs text-zinc-500">Maks. 100MB</span>
        </div>
      </div>

      <!-- File Selected: Page Grid & Controls -->
      <div v-else class="space-y-6">
        <!-- File Header -->
        <div class="bg-[#14171d] border border-[#232732] rounded-xl p-4 flex items-center justify-between">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
              <FileText class="w-5 h-5" />
            </div>
            <div class="min-w-0">
              <h4 class="text-sm font-semibold text-zinc-200 truncate">
                {{ selectedFile.name }}
              </h4>
              <p class="text-xs text-zinc-400 font-mono mt-0.5">
                {{ formatBytes(selectedFile.size) }} &bull; Total {{ totalPages }} Halaman
              </p>
            </div>
          </div>

          <button
            @click="resetAll"
            class="text-xs text-zinc-400 hover:text-rose-400 p-2 rounded hover:bg-[#1e232d] transition"
            title="Ganti file"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Toolbar: Range Input & Quick Buttons -->
        <div class="bg-[#13161c] border border-[#222630] rounded-xl p-4 space-y-4 shadow-xl">
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <!-- Range Input -->
            <div class="flex-1 max-w-md">
              <label class="block text-xs font-mono text-zinc-400 uppercase tracking-wider mb-1.5">
                Pilih Lewat Teks Rentang (Range):
              </label>
              <div class="relative">
                <input
                  v-model="rangeInput"
                  @input="applyRangeInput"
                  type="text"
                  placeholder="Contoh: 1-3, 5, 8-10"
                  class="w-full bg-[#1b1f28] border border-[#2c3342] rounded-lg px-3.5 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:border-amber-500 focus:outline-none font-mono"
                />
              </div>
            </div>

            <!-- Selection Shortcuts -->
            <div>
              <span class="block text-xs font-mono text-zinc-400 uppercase tracking-wider mb-1.5">
                Aksi Cepat:
              </span>
              <div class="flex flex-wrap items-center gap-1.5">
                <button
                  @click="selectAll"
                  class="px-2.5 py-1.5 text-xs bg-[#1f242e] hover:bg-[#282f3d] text-zinc-300 rounded border border-[#2d3544] transition"
                >
                  Semua
                </button>
                <button
                  @click="deselectAll"
                  class="px-2.5 py-1.5 text-xs bg-[#1f242e] hover:bg-[#282f3d] text-zinc-300 rounded border border-[#2d3544] transition"
                >
                  Hapus Pilihan
                </button>
                <button
                  @click="selectOdd"
                  class="px-2.5 py-1.5 text-xs bg-[#1f242e] hover:bg-[#282f3d] text-zinc-300 rounded border border-[#2d3544] transition"
                >
                  Ganjil
                </button>
                <button
                  @click="selectEven"
                  class="px-2.5 py-1.5 text-xs bg-[#1f242e] hover:bg-[#282f3d] text-zinc-300 rounded border border-[#2d3544] transition"
                >
                  Genap
                </button>
                <button
                  @click="invertSelection"
                  class="px-2.5 py-1.5 text-xs bg-[#1f242e] hover:bg-[#282f3d] text-zinc-300 rounded border border-[#2d3544] transition"
                >
                  Balik Pilihan
                </button>
              </div>
            </div>
          </div>

          <!-- Mode & Info -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-[#1f232b]">
            <div class="flex items-center gap-4">
              <span class="text-xs font-mono text-zinc-400 uppercase">Format Output:</span>
              <label class="flex items-center gap-2 cursor-pointer text-xs text-zinc-300">
                <input
                  type="radio"
                  v-model="splitMode"
                  value="single"
                  class="accent-amber-500"
                />
                <span>Satu File Gabungan (.PDF)</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer text-xs text-zinc-300">
                <input
                  type="radio"
                  v-model="splitMode"
                  value="zip"
                  class="accent-amber-500"
                />
                <span>Pisah Tiap Halaman (.ZIP)</span>
              </label>
            </div>

            <span class="text-xs font-mono text-amber-400">
              {{ selectedPages.length }} dari {{ totalPages }} halaman dipilih
            </span>
          </div>
        </div>

        <!-- Thumbnails Loading State -->
        <div
          v-if="isLoadingThumbs"
          class="h-48 rounded-xl bg-[#13151b] border border-[#222630] flex flex-col items-center justify-center gap-2 text-zinc-400"
        >
          <Loader2 class="w-6 h-6 animate-spin text-amber-400" />
          <span class="text-xs font-mono">Memuat preview halaman dari Poppler...</span>
        </div>

        <!-- Visual Page Grid -->
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3.5">
          <div
            v-for="page in pagesList"
            :key="page.page"
            @click="togglePage(page.page)"
            :class="[
              'cursor-pointer border rounded-xl overflow-hidden transition relative flex flex-col group select-none',
              selectedPages.includes(page.page)
                ? 'border-amber-500 bg-amber-500/10 shadow-md shadow-amber-500/10'
                : 'border-[#232833] bg-[#14171d] hover:border-[#384152] opacity-60 hover:opacity-100'
            ]"
          >
            <!-- Checkbox pill in top-right -->
            <div class="absolute top-2 right-2 z-10">
              <div
                :class="[
                  'w-5 h-5 rounded-md flex items-center justify-center text-xs font-bold transition shadow',
                  selectedPages.includes(page.page)
                    ? 'bg-amber-500 text-black'
                    : 'bg-black/70 border border-[#394254] text-transparent'
                ]"
              >
                <Check class="w-3.5 h-3.5" />
              </div>
            </div>

            <!-- Page Number pill in top-left -->
            <div class="absolute top-2 left-2 z-10">
              <span class="px-1.5 py-0.5 rounded bg-black/80 backdrop-blur-sm text-[10px] font-mono font-semibold text-zinc-300">
                #{{ page.page }}
              </span>
            </div>

            <!-- Thumbnail Image -->
            <div class="h-44 bg-[#0d0e12] flex items-center justify-center overflow-hidden p-2">
              <img
                v-if="page.dataUrl"
                :src="page.dataUrl"
                :alt="`Halaman ${page.page}`"
                class="max-h-full max-w-full object-contain rounded shadow"
              />
              <div v-else class="flex flex-col items-center justify-center text-zinc-600">
                <FileText class="w-8 h-8 mb-1" />
                <span class="text-[10px] font-mono">Hal {{ page.page }}</span>
              </div>
            </div>

            <!-- Footer indicator -->
            <div
              :class="[
                'py-1 text-center text-[10px] font-mono border-t transition',
                selectedPages.includes(page.page)
                  ? 'border-amber-500/30 text-amber-300 font-semibold'
                  : 'border-[#202530] text-zinc-400'
              ]"
            >
              {{ selectedPages.includes(page.page) ? 'Terpilih' : 'Lewati' }}
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-between pt-4 border-t border-[#232732]">
          <span class="text-xs text-zinc-400 font-mono">
            {{ selectedPages.length > 0 ? `Siap mengekstrak ${selectedPages.length} halaman.` : 'Pilih minimal satu halaman.' }}
          </span>

          <button
            @click="splitPdf"
            :disabled="selectedPages.length === 0 || isProcessing"
            class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-black font-semibold text-sm rounded-lg flex items-center gap-2 shadow-lg shadow-amber-500/10 transition active:scale-95"
          >
            <Loader2 v-if="isProcessing" class="w-4 h-4 animate-spin" />
            <Split v-else class="w-4 h-4" />
            <span>{{ isProcessing ? 'Memisahkan PDF...' : `Ekstrak ${selectedPages.length} Halaman` }}</span>
          </button>
        </div>
      </div>

      <!-- Error alert -->
      <div
        v-if="errorMessage"
        class="bg-rose-500/10 border border-rose-500/30 rounded-xl p-4 flex items-center gap-3 text-rose-300 text-sm"
      >
        <AlertCircle class="w-5 h-5 shrink-0 text-rose-400" />
        <span>{{ errorMessage }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import {
  Split,
  FileText,
  X,
  Download,
  CheckCircle2,
  Check,
  Loader2,
  AlertCircle
} from 'lucide-vue-next';
import { formatBytes } from '@/Utils/format';

const emit = defineEmits(['operation-completed']);

const fileInputRef = ref(null);
const selectedFile = ref(null);
const isDragging = ref(false);
const isLoadingThumbs = ref(false);
const isProcessing = ref(false);
const errorMessage = ref('');
const result = ref(null);

const totalPages = ref(0);
const pagesList = ref([]);
const selectedPages = ref([]);
const rangeInput = ref('');
const splitMode = ref('single');

function triggerFileInput() {
  fileInputRef.value?.click();
}

function handleFileInput(e) {
  const file = e.target.files?.[0];
  if (file) setPdfFile(file);
  e.target.value = '';
}

function handleDrop(e) {
  isDragging.value = false;
  const file = e.dataTransfer.files?.[0];
  if (file) setPdfFile(file);
}

async function setPdfFile(file) {
  errorMessage.value = '';
  if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
    errorMessage.value = 'Harap pilih file dokumen berformat PDF (.pdf).';
    return;
  }

  selectedFile.value = file;
  isLoadingThumbs.value = true;

  const formData = new FormData();
  formData.append('file', file);
  formData.append('max_pages', '100');

  try {
    const res = await fetch('/pdf/thumbnails', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: formData,
    });

    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Gagal merender halaman.');
    }

    totalPages.value = data.page_count;
    pagesList.value = data.thumbnails;

    // Default: select all
    selectedPages.value = data.thumbnails.map((p) => p.page);
    syncRangeFromSelection();
  } catch (err) {
    errorMessage.value = err.message || 'Gagal memuat pratinjau halaman PDF.';
  } finally {
    isLoadingThumbs.value = false;
  }
}

function togglePage(pageNum) {
  const idx = selectedPages.value.indexOf(pageNum);
  if (idx > -1) {
    selectedPages.value.splice(idx, 1);
  } else {
    selectedPages.value.push(pageNum);
    selectedPages.value.sort((a, b) => a - b);
  }
  syncRangeFromSelection();
}

function selectAll() {
  selectedPages.value = pagesList.value.map((p) => p.page);
  syncRangeFromSelection();
}

function deselectAll() {
  selectedPages.value = [];
  rangeInput.value = '';
}

function selectOdd() {
  selectedPages.value = pagesList.value.filter((p) => p.page % 2 !== 0).map((p) => p.page);
  syncRangeFromSelection();
}

function selectEven() {
  selectedPages.value = pagesList.value.filter((p) => p.page % 2 === 0).map((p) => p.page);
  syncRangeFromSelection();
}

function invertSelection() {
  const all = pagesList.value.map((p) => p.page);
  selectedPages.value = all.filter((p) => !selectedPages.value.includes(p));
  syncRangeFromSelection();
}

function syncRangeFromSelection() {
  if (selectedPages.value.length === 0) {
    rangeInput.value = '';
    return;
  }

  // Format into range notation: 1-3, 5, 8-10
  const sorted = [...selectedPages.value].sort((a, b) => a - b);
  const ranges = [];
  let start = sorted[0];
  let end = start;

  for (let i = 1; i < sorted.length; i++) {
    if (sorted[i] === end + 1) {
      end = sorted[i];
    } else {
      ranges.push(start === end ? `${start}` : `${start}-${end}`);
      start = sorted[i];
      end = start;
    }
  }
  ranges.push(start === end ? `${start}` : `${start}-${end}`);
  rangeInput.value = ranges.join(', ');
}

function applyRangeInput() {
  const parsed = [];
  const parts = rangeInput.value.split(',');

  for (const part of parts) {
    const trimmed = part.trim();
    if (!trimmed) continue;

    if (trimmed.includes('-')) {
      const [s, e] = trimmed.split('-');
      const start = parseInt(s, 10);
      const end = parseInt(e, 10);
      if (!isNaN(start) && !isNaN(end) && start > 0 && end >= start) {
        for (let i = start; i <= Math.min(end, totalPages.value); i++) {
          parsed.push(i);
        }
      }
    } else {
      const p = parseInt(trimmed, 10);
      if (!isNaN(p) && p > 0 && p <= totalPages.value) {
        parsed.push(p);
      }
    }
  }

  selectedPages.value = Array.from(new Set(parsed)).sort((a, b) => a - b);
}

async function splitPdf() {
  if (selectedPages.value.length === 0 || !selectedFile.value) return;

  isProcessing.value = true;
  errorMessage.value = '';

  const formData = new FormData();
  formData.append('file', selectedFile.value);
  formData.append('mode', splitMode.value);
  selectedPages.value.forEach((p) => {
    formData.append('pages[]', p);
  });

  try {
    const res = await fetch('/pdf/split', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: formData,
    });

    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Gagal memisahkan PDF.');
    }

    result.value = data;
    emit('operation-completed', data.history);
  } catch (err) {
    errorMessage.value = err.message || 'Terjadi kesalahan saat memproses file.';
  } finally {
    isProcessing.value = false;
  }
}

function resetAll() {
  result.value = null;
  selectedFile.value = null;
  pagesList.value = [];
  selectedPages.value = [];
  rangeInput.value = '';
  errorMessage.value = '';
}
</script>
