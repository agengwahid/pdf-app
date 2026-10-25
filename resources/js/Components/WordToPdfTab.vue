<template>
  <div class="space-y-6">
    <!-- Success State -->
    <div
      v-if="result"
      class="bg-[#141b18] border border-emerald-500/30 rounded-xl p-6 relative overflow-hidden shadow-2xl"
    >
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
            <CheckCircle2 class="w-6 h-6" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-mono uppercase px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-semibold border border-emerald-500/30">
                Konversi Berhasil
              </span>
              <span class="text-xs text-zinc-400 font-mono">
                {{ formatBytes(result.result_size) }} &bull; {{ result.page_count }} Halaman &bull; {{ result.duration_ms }} ms
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
            Konversi File Lain
          </button>
        </div>
      </div>
    </div>

    <!-- Upload and Form State -->
    <div v-else class="space-y-6">
      <!-- File Dropzone (if not yet chosen) -->
      <div
        v-if="!selectedFile"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        :class="[
          'relative border-2 border-dashed rounded-xl p-10 transition flex flex-col items-center justify-center text-center cursor-pointer',
          isDragging
            ? 'border-blue-500 bg-blue-500/5'
            : 'border-[#262b35] hover:border-[#3a4252] bg-[#121419]/70 hover:bg-[#15181e]'
        ]"
        @click="triggerFileInput"
      >
        <input
          ref="fileInputRef"
          type="file"
          accept=".docx,.doc,.odt,.rtf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword"
          class="hidden"
          @change="handleFileInput"
        />

        <div class="w-14 h-14 rounded-2xl bg-[#151c28] border border-[#233147] flex items-center justify-center text-blue-400 mb-4 shadow-inner">
          <FileType class="w-7 h-7" />
        </div>

        <h3 class="text-base font-semibold text-zinc-100">
          Pilih File Word (.docx, .doc, .odt, .rtf)
        </h3>
        <p class="text-xs text-zinc-400 mt-1 max-w-sm">
          Tarik & lepas file dokumen Word ke sini untuk dikonversi menjadi file PDF berkualitas tinggi secara offline.
        </p>

        <div class="mt-4 flex items-center gap-2">
          <span class="px-3 py-1.5 rounded-lg bg-[#1f242e] border border-[#2c3340] text-xs font-mono text-zinc-300">
            Jelajahi Dokumen
          </span>
          <span class="text-xs text-zinc-500">Maks. 100MB</span>
        </div>
      </div>

      <!-- File Selected State -->
      <div v-else class="space-y-6">
        <!-- Selected File Banner -->
        <div class="bg-[#14171d] border border-[#232732] rounded-xl p-5 flex items-center justify-between">
          <div class="flex items-center gap-4 min-w-0">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
              <FileType class="w-6 h-6" />
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-mono uppercase px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 font-semibold border border-blue-500/30">
                  {{ getFileExtension(selectedFile.name) }}
                </span>
                <span class="text-xs font-mono text-zinc-400">
                  {{ formatBytes(selectedFile.size) }}
                </span>
              </div>
              <h4 class="text-sm font-semibold text-zinc-100 truncate mt-1">
                {{ selectedFile.name }}
              </h4>
            </div>
          </div>

          <button
            @click="selectedFile = null"
            class="text-xs text-zinc-400 hover:text-rose-400 p-2 rounded hover:bg-[#1e232d] transition"
            title="Ganti file"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Conversion Details Info Box -->
        <div class="bg-[#12151c] border border-[#222836] rounded-xl p-5 space-y-3">
          <h4 class="text-xs font-mono font-semibold uppercase text-zinc-400 tracking-wider">
            Informasi Engine Konversi Lokal:
          </h4>
          <ul class="text-xs text-zinc-400 space-y-2">
            <li class="flex items-start gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-blue-400 mt-1.5 shrink-0"></span>
              <span>Dikonversi menggunakan <strong>LibreOffice 24 Headless Engine</strong> yang terpasang di komputer lokal Anda.</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-blue-400 mt-1.5 shrink-0"></span>
              <span>Mempertahankan tata letak tabel, orientasi halaman, font, header, footer, dan gambar beresolusi tinggi.</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-blue-400 mt-1.5 shrink-0"></span>
              <span>100% aman dan privat tanpa transmisi data melalui jaringan internet.</span>
            </li>
          </ul>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end pt-2">
          <button
            @click="convertDoc"
            :disabled="isProcessing"
            class="px-6 py-2.5 bg-blue-500 hover:bg-blue-400 disabled:opacity-50 text-white font-semibold text-sm rounded-lg flex items-center gap-2 shadow-lg shadow-blue-500/10 transition active:scale-95"
          >
            <Loader2 v-if="isProcessing" class="w-4 h-4 animate-spin" />
            <FileOutput v-else class="w-4 h-4" />
            <span>{{ isProcessing ? 'Mengonversi Dokumen...' : 'Konversi ke Dokumen PDF' }}</span>
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
  FileType,
  FileOutput,
  X,
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
const isProcessing = ref(false);
const errorMessage = ref('');
const result = ref(null);

function triggerFileInput() {
  fileInputRef.value?.click();
}

function handleFileInput(e) {
  const file = e.target.files?.[0];
  if (file) setWordFile(file);
  e.target.value = '';
}

function handleDrop(e) {
  isDragging.value = false;
  const file = e.dataTransfer.files?.[0];
  if (file) setWordFile(file);
}

function getFileExtension(filename) {
  if (!filename) return '';
  const ext = filename.split('.').pop() || '';
  return ext.toUpperCase();
}

function setWordFile(file) {
  errorMessage.value = '';
  const validExts = ['.docx', '.doc', '.odt', '.rtf'];
  const hasValidExt = validExts.some((ext) => file.name.toLowerCase().endsWith(ext));

  if (!hasValidExt) {
    errorMessage.value = 'Harap pilih file dokumen Word atau teks berformat .docx, .doc, .odt, atau .rtf.';
    return;
  }

  selectedFile.value = file;
}

async function convertDoc() {
  if (!selectedFile.value) return;

  isProcessing.value = true;
  errorMessage.value = '';

  const formData = new FormData();
  formData.append('file', selectedFile.value);

  try {
    const res = await fetch('/pdf/word-to-pdf', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: formData,
    });

    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Gagal mengonversi file Word.');
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
  errorMessage.value = '';
}
</script>
