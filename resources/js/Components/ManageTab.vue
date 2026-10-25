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
                Selesai Ditata
              </span>
              <span class="text-xs text-zinc-400 font-mono">
                {{ formatBytes(result.result_size) }} &bull; {{ result.total_pages }} Halaman Disimpan
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
            Unduh PDF Hasil
          </a>
          <button
            @click="resetAll"
            class="px-4 py-2.5 bg-[#1c222b] hover:bg-[#252c38] text-zinc-300 text-sm font-medium rounded-lg border border-[#2d3443] transition"
          >
            Tata Dokumen Lain
          </button>
        </div>
      </div>
    </div>

    <!-- Upload & Interactive Page Grid -->
    <div v-else class="space-y-6">
      <!-- File dropzone (if no file) -->
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
          <Layers class="w-7 h-7" />
        </div>

        <h3 class="text-base font-semibold text-zinc-100">
          Pilih File PDF untuk Mengatur Halaman
        </h3>
        <p class="text-xs text-zinc-400 mt-1 max-w-sm">
          Atur ulang urutan halaman (reorder), putar orientasi (rotate 90°/180°/270°), atau hapus halaman yang tidak diperlukan.
        </p>

        <div class="mt-4 flex items-center gap-2">
          <span class="px-3 py-1.5 rounded-lg bg-[#1f242e] border border-[#2c3340] text-xs font-mono text-zinc-300">
            Pilih Dokumen PDF
          </span>
          <span class="text-xs text-zinc-500">Maks. 100MB</span>
        </div>
      </div>

      <!-- File loaded: Interactive Visual Canvas -->
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
                {{ formatBytes(selectedFile.size) }} &bull; Total {{ pages.length }} Halaman
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

        <!-- Global Batch Actions Toolbar -->
        <div class="bg-[#13161c] border border-[#222630] rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-xl">
          <div class="flex items-center gap-2 text-xs font-mono text-zinc-400">
            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            <span>{{ activePagesCount }} dari {{ pages.length }} halaman aktif</span>
            <span v-if="deletedPagesCount > 0" class="text-rose-400">
              ({{ deletedPagesCount }} terhapus)
            </span>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex flex-wrap items-center gap-2">
            <button
              @click="rotateAll"
              class="px-2.5 py-1.5 text-xs bg-[#1f242e] hover:bg-[#282f3d] text-zinc-300 rounded border border-[#2d3544] transition flex items-center gap-1.5"
              title="Putar semua halaman aktif 90 derajat searah jarum jam"
            >
              <RotateCw class="w-3.5 h-3.5 text-amber-400" />
              Putar Semua +90°
            </button>

            <button
              @click="resetAllRotations"
              class="px-2.5 py-1.5 text-xs bg-[#1f242e] hover:bg-[#282f3d] text-zinc-300 rounded border border-[#2d3544] transition flex items-center gap-1.5"
            >
              <RotateCcw class="w-3.5 h-3.5" />
              Reset Rotasi
            </button>

            <button
              v-if="deletedPagesCount > 0"
              @click="restoreAllDeleted"
              class="px-2.5 py-1.5 text-xs bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 rounded border border-emerald-500/30 transition flex items-center gap-1.5"
            >
              <Undo2 class="w-3.5 h-3.5" />
              Kembalikan Terhapus
            </button>

            <button
              @click="reverseOrder"
              class="px-2.5 py-1.5 text-xs bg-[#1f242e] hover:bg-[#282f3d] text-zinc-300 rounded border border-[#2d3544] transition flex items-center gap-1.5"
            >
              <ArrowLeftRight class="w-3.5 h-3.5" />
              Balik Urutan
            </button>
          </div>
        </div>

        <!-- Thumbnails Loading State -->
        <div
          v-if="isLoadingThumbs"
          class="h-56 rounded-xl bg-[#13151b] border border-[#222630] flex flex-col items-center justify-center gap-2 text-zinc-400"
        >
          <Loader2 class="w-6 h-6 animate-spin text-amber-400" />
          <span class="text-xs font-mono">Memuat halaman PDF untuk pengeditan visual...</span>
        </div>

        <!-- Page Grid with Reorder, Rotate, and Delete Controls -->
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
          <div
            v-for="(item, index) in pages"
            :key="item.id"
            draggable="true"
            @dragstart="onDragStart(index, $event)"
            @dragover.prevent="onDragOver(index)"
            @drop="onDrop(index)"
            @dragend="onDragEnd"
            :class="[
              'border rounded-xl overflow-hidden transition relative flex flex-col bg-[#14171e] group select-none shadow-md',
              item.deleted
                ? 'opacity-40 border-rose-500/30 bg-rose-950/10'
                : dragOverIndex === index
                ? 'border-amber-500 bg-amber-500/10 scale-105'
                : 'border-[#242936] hover:border-[#3a4356]'
            ]"
          >
            <!-- Top Overlay: Order Number & Rotation Angle -->
            <div class="px-2.5 py-1.5 bg-[#101217] border-b border-[#202530] flex items-center justify-between text-[11px] font-mono">
              <div class="flex items-center gap-1.5">
                <span class="w-5 h-5 rounded bg-[#1e232e] text-amber-400 font-bold flex items-center justify-center text-[10px]">
                  {{ index + 1 }}
                </span>
                <span class="text-zinc-500 text-[10px]">
                  (Asli: #{{ item.page }})
                </span>
              </div>

              <span
                v-if="item.rotation !== 0"
                class="px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-300 text-[10px] font-semibold"
              >
                {{ item.rotation }}°
              </span>
            </div>

            <!-- Thumbnail Visual Canvas -->
            <div class="h-44 bg-[#0a0b0e] flex items-center justify-center p-3 overflow-hidden relative">
              <img
                v-if="item.dataUrl"
                :src="item.dataUrl"
                :alt="`Halaman ${item.page}`"
                :style="{
                  transform: `rotate(${item.rotation}deg)`,
                  transition: 'transform 0.25s ease-out',
                }"
                class="max-h-full max-w-full object-contain rounded shadow pointer-events-none"
              />
              <div v-else class="flex flex-col items-center justify-center text-zinc-600">
                <FileText class="w-8 h-8 mb-1" />
                <span class="text-[10px] font-mono">Hal {{ item.page }}</span>
              </div>

              <!-- Deleted Overlay -->
              <div
                v-if="item.deleted"
                class="absolute inset-0 bg-black/70 backdrop-blur-[1px] flex flex-col items-center justify-center text-center p-2"
              >
                <Trash2 class="w-6 h-6 text-rose-400 mb-1" />
                <span class="text-xs font-semibold text-rose-300">Dihapus</span>
                <button
                  @click.stop="item.deleted = false"
                  class="mt-2 px-2 py-1 bg-[#1e2430] hover:bg-[#283040] text-zinc-200 text-[10px] rounded border border-[#343e52] transition"
                >
                  Urungkan
                </button>
              </div>
            </div>

            <!-- Bottom Interactive Controls -->
            <div class="p-1.5 bg-[#12151c] border-t border-[#1e222c] flex items-center justify-between gap-1">
              <!-- Reorder Arrow Left -->
              <button
                @click="movePage(index, -1)"
                :disabled="index === 0"
                class="p-1 rounded text-zinc-400 hover:text-white hover:bg-[#222733] disabled:opacity-20 transition"
                title="Pindah ke kiri"
              >
                <ArrowLeft class="w-3.5 h-3.5" />
              </button>

              <!-- Rotate CW 90° -->
              <button
                @click="rotatePage(index)"
                :disabled="item.deleted"
                class="px-2 py-1 rounded text-zinc-300 hover:text-amber-300 hover:bg-amber-500/10 text-[11px] font-mono flex items-center gap-1 transition"
                title="Putar 90 derajat searah jarum jam"
              >
                <RotateCw class="w-3.5 h-3.5 text-amber-400" />
                <span>+90°</span>
              </button>

              <!-- Delete Page -->
              <button
                v-if="!item.deleted"
                @click="item.deleted = true"
                class="p-1 rounded text-zinc-500 hover:text-rose-400 hover:bg-rose-500/10 transition"
                title="Hapus halaman ini"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </button>

              <!-- Reorder Arrow Right -->
              <button
                @click="movePage(index, 1)"
                :disabled="index === pages.length - 1"
                class="p-1 rounded text-zinc-400 hover:text-white hover:bg-[#222733] disabled:opacity-20 transition"
                title="Pindah ke kanan"
              >
                <ArrowRight class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Submit Bar -->
        <div class="flex items-center justify-between pt-4 border-t border-[#232732]">
          <span class="text-xs text-zinc-400 font-mono">
            {{ activePagesCount }} halaman akan disimpan dalam urutan baru.
          </span>

          <button
            @click="saveManagedPdf"
            :disabled="activePagesCount === 0 || isProcessing"
            class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-black font-semibold text-sm rounded-lg flex items-center gap-2 shadow-lg shadow-amber-500/10 transition active:scale-95"
          >
            <Loader2 v-if="isProcessing" class="w-4 h-4 animate-spin" />
            <Save v-else class="w-4 h-4" />
            <span>{{ isProcessing ? 'Menata Dokumen PDF...' : `Simpan & Unduh (${activePagesCount} Hal)` }}</span>
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
import { ref, computed } from 'vue';
import {
  Layers,
  FileText,
  X,
  RotateCw,
  RotateCcw,
  Undo2,
  ArrowLeftRight,
  ArrowLeft,
  ArrowRight,
  Trash2,
  Save,
  Download,
  CheckCircle2,
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

const pages = ref([]);
const draggedIndex = ref(null);
const dragOverIndex = ref(null);

const activePagesCount = computed(() => {
  return pages.value.filter((p) => !p.deleted).length;
});

const deletedPagesCount = computed(() => {
  return pages.value.filter((p) => p.deleted).length;
});

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

    pages.value = data.thumbnails.map((t) => ({
      id: Math.random().toString(36).substring(2, 9),
      page: t.page,
      dataUrl: t.dataUrl,
      rotation: 0,
      deleted: false,
    }));
  } catch (err) {
    errorMessage.value = err.message || 'Gagal memuat pratinjau halaman PDF.';
  } finally {
    isLoadingThumbs.value = false;
  }
}

// Drag & drop handlers
function onDragStart(index, event) {
  draggedIndex.value = index;
  event.dataTransfer.effectAllowed = 'move';
}

function onDragOver(index) {
  dragOverIndex.value = index;
}

function onDrop(dropIndex) {
  if (draggedIndex.value === null || draggedIndex.value === dropIndex) return;
  const item = pages.value.splice(draggedIndex.value, 1)[0];
  pages.value.splice(dropIndex, 0, item);
  draggedIndex.value = null;
  dragOverIndex.value = null;
}

function onDragEnd() {
  draggedIndex.value = null;
  dragOverIndex.value = null;
}

function movePage(index, offset) {
  const target = index + offset;
  if (target < 0 || target >= pages.value.length) return;
  const item = pages.value.splice(index, 1)[0];
  pages.value.splice(target, 0, item);
}

function rotatePage(index) {
  const current = pages.value[index].rotation;
  pages.value[index].rotation = (current + 90) % 360;
}

function rotateAll() {
  pages.value.forEach((p) => {
    if (!p.deleted) {
      p.rotation = (p.rotation + 90) % 360;
    }
  });
}

function resetAllRotations() {
  pages.value.forEach((p) => {
    p.rotation = 0;
  });
}

function restoreAllDeleted() {
  pages.value.forEach((p) => {
    p.deleted = false;
  });
}

function reverseOrder() {
  pages.value.reverse();
}

async function saveManagedPdf() {
  const active = pages.value.filter((p) => !p.deleted);
  if (active.length === 0 || !selectedFile.value) {
    errorMessage.value = 'Tidak ada halaman aktif yang dipilih.';
    return;
  }

  isProcessing.value = true;
  errorMessage.value = '';

  const config = active.map((p) => ({
    page: p.page,
    rotation: p.rotation,
  }));

  const formData = new FormData();
  formData.append('file', selectedFile.value);
  formData.append('pages_config', JSON.stringify(config));

  try {
    const res = await fetch('/pdf/manage', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: formData,
    });

    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Gagal menyimpan tata letak PDF.');
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
  pages.value = [];
  errorMessage.value = '';
}
</script>
