<template>
  <div class="space-y-6">
    <!-- Success State -->
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
                Selesai
              </span>
              <span class="text-xs text-zinc-400 font-mono">
                {{ formatBytes(result.result_size) }} &bull; {{ result.page_count }} Halaman
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
            Unduh Hasil PDF
          </a>
          <button
            @click="resetAll"
            class="px-4 py-2.5 bg-[#1c222b] hover:bg-[#252c38] text-zinc-300 text-sm font-medium rounded-lg border border-[#2d3443] transition"
          >
            Gabung Lagi
          </button>
        </div>
      </div>
    </div>

    <!-- Upload and File List -->
    <div v-else class="space-y-6">
      <!-- Drag & Drop Zone -->
      <div
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        :class="[
          'relative border-2 border-dashed rounded-xl p-8 transition flex flex-col items-center justify-center text-center cursor-pointer',
          isDragging
            ? 'border-amber-500 bg-amber-500/5'
            : 'border-[#262b35] hover:border-[#3a4252] bg-[#121419]/70 hover:bg-[#15181e]'
        ]"
        @click="triggerFileInput"
      >
        <input
          ref="fileInputRef"
          type="file"
          multiple
          accept="application/pdf"
          class="hidden"
          @change="handleFileInput"
        />

        <div class="w-14 h-14 rounded-2xl bg-[#191d24] border border-[#2a303c] flex items-center justify-center text-amber-400 mb-4 shadow-inner">
          <Files class="w-7 h-7" />
        </div>

        <h3 class="text-base font-semibold text-zinc-100">
          Tarik & Lepas File PDF ke Sini
        </h3>
        <p class="text-xs text-zinc-400 mt-1 max-w-sm">
          Pilih dua atau lebih file PDF untuk digabungkan menjadi satu dokumen berurutan.
        </p>

        <div class="mt-4 flex items-center gap-2">
          <span class="px-3 py-1.5 rounded-lg bg-[#1f242e] border border-[#2c3340] text-xs font-mono text-zinc-300">
            Jelajahi File
          </span>
          <span class="text-xs text-zinc-500">Maks. 100MB per file</span>
        </div>
      </div>

      <!-- File List Reordering -->
      <div v-if="files.length > 0" class="bg-[#121418] border border-[#222630] rounded-xl overflow-hidden shadow-xl">
        <!-- Toolbar -->
        <div class="px-5 py-3.5 bg-[#161920] border-b border-[#222630] flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-xs font-mono font-semibold uppercase text-zinc-400">
              Daftar File ({{ files.length }})
            </span>
            <span class="text-xs text-zinc-500 font-mono">
              Total: {{ formatBytes(totalSize) }}
            </span>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="triggerFileInput"
              class="px-2.5 py-1 text-xs text-zinc-300 hover:text-white bg-[#1f242e] hover:bg-[#282f3d] rounded border border-[#2d3544] transition flex items-center gap-1.5"
            >
              <Plus class="w-3.5 h-3.5" />
              Tambah File
            </button>
            <button
              @click="files = []"
              class="px-2.5 py-1 text-xs text-rose-400 hover:text-rose-300 bg-rose-500/10 hover:bg-rose-500/20 rounded border border-rose-500/20 transition flex items-center gap-1.5"
            >
              <Trash2 class="w-3.5 h-3.5" />
              Hapus Semua
            </button>
          </div>
        </div>

        <div class="p-3 text-xs text-zinc-400 bg-[#14161d] border-b border-[#1f232c] flex items-center gap-2">
          <Info class="w-4 h-4 text-amber-400 shrink-0" />
          <span>Tips: Seret (drag & drop) kartu file atau gunakan tombol panah untuk mengatur urutan penggabungan.</span>
        </div>

        <!-- Draggable items list -->
        <div class="p-4 space-y-2.5">
          <div
            v-for="(file, index) in files"
            :key="file.id"
            draggable="true"
            @dragstart="onDragStart(index, $event)"
            @dragover.prevent="onDragOver(index)"
            @drop="onDrop(index)"
            @dragend="onDragEnd"
            :class="[
              'group flex items-center justify-between p-3.5 rounded-lg border transition select-none',
              dragOverIndex === index
                ? 'border-amber-500 bg-amber-500/10'
                : 'bg-[#171a21] border-[#222733] hover:border-[#353d4e]'
            ]"
          >
            <!-- Left: Handle, Index, Name & Size -->
            <div class="flex items-center gap-3 min-w-0">
              <div
                class="cursor-grab active:cursor-grabbing text-zinc-500 group-hover:text-amber-400 transition"
                title="Tarik untuk memindahkan urutan"
              >
                <GripVertical class="w-4 h-4" />
              </div>

              <span class="w-6 h-6 rounded bg-[#202530] text-zinc-300 font-mono text-xs font-semibold flex items-center justify-center shrink-0">
                {{ String(index + 1).padStart(2, '0') }}
              </span>

              <div class="min-w-0">
                <p class="text-sm font-medium text-zinc-200 truncate">
                  {{ file.file.name }}
                </p>
                <p class="text-xs text-zinc-400 font-mono mt-0.5">
                  {{ formatBytes(file.file.size) }}
                </p>
              </div>
            </div>

            <!-- Right: Actions -->
            <div class="flex items-center gap-1.5 shrink-0 ml-4">
              <!-- Move Up -->
              <button
                @click="moveFile(index, -1)"
                :disabled="index === 0"
                class="p-1.5 rounded text-zinc-400 hover:text-white hover:bg-[#252b37] disabled:opacity-30 disabled:hover:bg-transparent transition"
                title="Pindah ke atas"
              >
                <ArrowUp class="w-4 h-4" />
              </button>

              <!-- Move Down -->
              <button
                @click="moveFile(index, 1)"
                :disabled="index === files.length - 1"
                class="p-1.5 rounded text-zinc-400 hover:text-white hover:bg-[#252b37] disabled:opacity-30 disabled:hover:bg-transparent transition"
                title="Pindah ke bawah"
              >
                <ArrowDown class="w-4 h-4" />
              </button>

              <!-- Delete File -->
              <button
                @click="removeFile(index)"
                class="p-1.5 rounded text-zinc-500 hover:text-rose-400 hover:bg-rose-500/10 transition ml-1"
                title="Hapus dari daftar"
              >
                <X class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Footer Settings & Merge Trigger -->
        <div class="p-5 bg-[#15181f] border-t border-[#222630] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex-1 max-w-sm">
            <label class="block text-xs font-mono text-zinc-400 mb-1.5">
              Nama File Hasil (Opsional):
            </label>
            <div class="relative">
              <input
                v-model="customName"
                type="text"
                placeholder="dokumen_gabungan"
                class="w-full bg-[#1b1f28] border border-[#2c3342] rounded-lg px-3 py-2 text-xs text-zinc-200 placeholder-zinc-500 focus:border-amber-500 focus:outline-none font-mono"
              />
              <span class="absolute right-3 top-2 text-xs text-zinc-500 font-mono">.pdf</span>
            </div>
          </div>

          <button
            @click="mergePdfs"
            :disabled="files.length < 2 || isProcessing"
            class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-black font-semibold text-sm rounded-lg flex items-center justify-center gap-2 shadow-lg shadow-amber-500/10 transition active:scale-95 disabled:active:scale-100"
          >
            <Loader2 v-if="isProcessing" class="w-4 h-4 animate-spin" />
            <Merge v-else class="w-4 h-4" />
            <span>{{ isProcessing ? 'Menggabungkan PDF...' : `Gabungkan ${files.length} File PDF` }}</span>
          </button>
        </div>
      </div>

      <!-- Error Message -->
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
  Files,
  Plus,
  Trash2,
  GripVertical,
  ArrowUp,
  ArrowDown,
  X,
  Merge,
  Download,
  CheckCircle2,
  Loader2,
  AlertCircle,
  Info
} from 'lucide-vue-next';
import { formatBytes } from '@/Utils/format';

const emit = defineEmits(['operation-completed']);

const fileInputRef = ref(null);
const files = ref([]);
const isDragging = ref(false);
const isProcessing = ref(false);
const errorMessage = ref('');
const customName = ref('');
const result = ref(null);

const draggedIndex = ref(null);
const dragOverIndex = ref(null);

const totalSize = computed(() => {
  return files.value.reduce((acc, curr) => acc + curr.file.size, 0);
});

function triggerFileInput() {
  fileInputRef.value?.click();
}

function handleFileInput(e) {
  const selected = Array.from(e.target.files || []);
  addValidPdfs(selected);
  e.target.value = '';
}

function handleDrop(e) {
  isDragging.value = false;
  const dropped = Array.from(e.dataTransfer.files || []);
  addValidPdfs(dropped);
}

function addValidPdfs(newFiles) {
  errorMessage.value = '';
  const pdfs = newFiles.filter((f) => f.type === 'application/pdf' || f.name.toLowerCase().endsWith('.pdf'));

  if (pdfs.length === 0 && newFiles.length > 0) {
    errorMessage.value = 'Harap pilih file berformat PDF (.pdf).';
    return;
  }

  for (const f of pdfs) {
    files.value.push({
      id: Math.random().toString(36).substring(2, 9),
      file: f,
    });
  }
}

function removeFile(index) {
  files.value.splice(index, 1);
}

function moveFile(fromIndex, offset) {
  const toIndex = fromIndex + offset;
  if (toIndex < 0 || toIndex >= files.value.length) return;
  const item = files.value.splice(fromIndex, 1)[0];
  files.value.splice(toIndex, 0, item);
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
  const item = files.value.splice(draggedIndex.value, 1)[0];
  files.value.splice(dropIndex, 0, item);
  draggedIndex.value = null;
  dragOverIndex.value = null;
}

function onDragEnd() {
  draggedIndex.value = null;
  dragOverIndex.value = null;
}

async function mergePdfs() {
  if (files.value.length < 2) {
    errorMessage.value = 'Diperlukan minimal 2 file PDF untuk digabungkan.';
    return;
  }

  isProcessing.value = true;
  errorMessage.value = '';

  const formData = new FormData();
  files.value.forEach((item) => {
    formData.append('files[]', item.file);
  });

  if (customName.value) {
    formData.append('custom_name', customName.value);
  }

  try {
    const response = await fetch('/pdf/merge', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: formData,
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Gagal menggabungkan PDF.');
    }

    result.value = data;
    emit('operation-completed', data.history);
  } catch (err) {
    errorMessage.value = err.message || 'Terjadi kesalahan saat memproses PDF.';
  } finally {
    isProcessing.value = false;
  }
}

function resetAll() {
  result.value = null;
  files.value = [];
  errorMessage.value = '';
  customName.value = '';
}
</script>
