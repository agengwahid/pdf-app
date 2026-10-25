<template>
  <div class="space-y-6">
    <!-- Result Comparison Card -->
    <div
      v-if="result"
      class="bg-[#13171f] border border-emerald-500/30 rounded-xl p-6 relative overflow-hidden shadow-2xl"
    >
      <div class="flex items-center gap-2 mb-4">
        <span class="text-xs font-mono uppercase px-2.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-semibold border border-emerald-500/30">
          Kompresi Berhasil
        </span>
        <span class="text-xs text-zinc-400 font-mono">
          Waktu: {{ result.duration_ms }} ms &bull; Ghostscript Local Engine
        </span>
      </div>

      <!-- Before & After Comparison Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-6">
        <!-- Before -->
        <div class="bg-[#191d26] border border-[#262c38] rounded-xl p-4">
          <p class="text-xs font-mono text-zinc-400 uppercase tracking-wider">Ukuran Sebelum</p>
          <p class="text-2xl font-bold font-mono text-zinc-300 mt-1">
            {{ formatBytes(result.original_size) }}
          </p>
          <p class="text-xs text-zinc-500 mt-1 truncate" :title="selectedFile?.name">
            {{ selectedFile?.name }}
          </p>
        </div>

        <!-- Reduction Badge -->
        <div class="bg-gradient-to-br from-emerald-950/40 to-[#191d26] border border-emerald-500/40 rounded-xl p-4 flex flex-col justify-between">
          <div>
            <p class="text-xs font-mono text-emerald-400 uppercase tracking-wider">Pengurangan Ukuran</p>
            <div class="flex items-baseline gap-2 mt-1">
              <span class="text-3xl font-extrabold font-mono text-emerald-300">
                -{{ result.reduction_percent }}%
              </span>
            </div>
          </div>
          <p class="text-xs text-emerald-400/80 font-mono mt-2">
            Hemat {{ formatBytes(result.original_size - result.result_size) }} ruang penyimpanan
          </p>
        </div>

        <!-- After -->
        <div class="bg-[#191d26] border border-[#262c38] rounded-xl p-4">
          <p class="text-xs font-mono text-zinc-400 uppercase tracking-wider">Ukuran Sesudah</p>
          <p class="text-2xl font-bold font-mono text-emerald-400 mt-1">
            {{ formatBytes(result.result_size) }}
          </p>
          <p class="text-xs text-zinc-500 mt-1 truncate">
            Preset: <span class="capitalize text-zinc-400 font-medium">{{ compressionLevel }}</span>
          </p>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-[#232936]">
        <div class="text-xs text-zinc-400 font-mono truncate max-w-sm">
          File tersimpan di: {{ result.result_name }}
        </div>
        <div class="flex items-center gap-3">
          <a
            :href="result.download_url"
            download
            class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-black font-semibold text-sm rounded-lg flex items-center gap-2 shadow-lg shadow-emerald-500/10 transition active:scale-95"
          >
            <Download class="w-4 h-4" />
            Unduh PDF Terkompresi
          </a>
          <button
            @click="resetAll"
            class="px-4 py-2.5 bg-[#1f242e] hover:bg-[#282f3d] text-zinc-300 text-sm font-medium rounded-lg border border-[#2c3342] transition"
          >
            Kompres File Lain
          </button>
        </div>
      </div>
    </div>

    <!-- Upload and Configuration State -->
    <div v-else class="space-y-6">
      <!-- File Selector (if not yet chosen) -->
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
          <Minimize2 class="w-7 h-7" />
        </div>

        <h3 class="text-base font-semibold text-zinc-100">
          Pilih File PDF untuk Dikompres
        </h3>
        <p class="text-xs text-zinc-400 mt-1 max-w-sm">
          Tarik & lepas file PDF ke area ini atau klik untuk memilih file dari komputer Anda.
        </p>

        <div class="mt-4 flex items-center gap-2">
          <span class="px-3 py-1.5 rounded-lg bg-[#1f242e] border border-[#2c3340] text-xs font-mono text-zinc-300">
            Pilih Dokumen PDF
          </span>
          <span class="text-xs text-zinc-500">Maks. 100MB</span>
        </div>
      </div>

      <!-- File Selected State & Compression Settings -->
      <div v-else class="space-y-6">
        <!-- Selected File Banner -->
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
                Ukuran Awal: {{ formatBytes(selectedFile.size) }}
              </p>
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

        <!-- Compression Presets Cards -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <label class="text-xs font-mono font-semibold uppercase text-zinc-400 tracking-wider">
              Pilih Tingkat Kompresi
            </label>
            <span class="text-xs text-zinc-500 font-mono">
              Ghostscript Output Profiles
            </span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Low Preset -->
            <div
              @click="compressionLevel = 'low'"
              :class="[
                'cursor-pointer border rounded-xl p-5 transition flex flex-col justify-between relative',
                compressionLevel === 'low'
                  ? 'bg-amber-500/10 border-amber-500 shadow-lg shadow-amber-500/5'
                  : 'bg-[#15181f] border-[#242935] hover:border-[#384050]'
              ]"
            >
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-mono px-2 py-0.5 rounded bg-[#202530] text-zinc-300 font-semibold">
                    300 DPI
                  </span>
                  <span
                    v-if="compressionLevel === 'low'"
                    class="w-2.5 h-2.5 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"
                  ></span>
                </div>
                <h4 class="text-sm font-bold text-zinc-100">Kompresi Ringan</h4>
                <p class="text-xs text-zinc-400 mt-1 leading-relaxed">
                  Kualitas gambar maksimal. Sangat direkomendasikan untuk dokumen resmi yang akan dicetak fisik.
                </p>
              </div>
              <div class="mt-4 pt-3 border-t border-[#232836] text-[11px] font-mono text-zinc-500">
                Pengurangan estimasi: ~15% - 35%
              </div>
            </div>

            <!-- Medium Preset -->
            <div
              @click="compressionLevel = 'medium'"
              :class="[
                'cursor-pointer border rounded-xl p-5 transition flex flex-col justify-between relative',
                compressionLevel === 'medium'
                  ? 'bg-amber-500/10 border-amber-500 shadow-lg shadow-amber-500/5'
                  : 'bg-[#15181f] border-[#242935] hover:border-[#384050]'
              ]"
            >
              <div>
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center gap-1.5">
                    <span class="text-xs font-mono px-2 py-0.5 rounded bg-[#202530] text-zinc-300 font-semibold">
                      150 DPI
                    </span>
                    <span class="text-[10px] font-mono uppercase px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 font-semibold">
                      Direkomendasikan
                    </span>
                  </div>
                  <span
                    v-if="compressionLevel === 'medium'"
                    class="w-2.5 h-2.5 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"
                  ></span>
                </div>
                <h4 class="text-sm font-bold text-zinc-100">Kompresi Sedang</h4>
                <p class="text-xs text-zinc-400 mt-1 leading-relaxed">
                  Keseimbangan terbaik antara ukuran file yang hemat dan ketajaman teks/gambar pada layar.
                </p>
              </div>
              <div class="mt-4 pt-3 border-t border-[#232836] text-[11px] font-mono text-zinc-500">
                Pengurangan estimasi: ~40% - 65%
              </div>
            </div>

            <!-- High Preset -->
            <div
              @click="compressionLevel = 'high'"
              :class="[
                'cursor-pointer border rounded-xl p-5 transition flex flex-col justify-between relative',
                compressionLevel === 'high'
                  ? 'bg-amber-500/10 border-amber-500 shadow-lg shadow-amber-500/5'
                  : 'bg-[#15181f] border-[#242935] hover:border-[#384050]'
              ]"
            >
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-mono px-2 py-0.5 rounded bg-[#202530] text-zinc-300 font-semibold">
                    72 DPI
                  </span>
                  <span
                    v-if="compressionLevel === 'high'"
                    class="w-2.5 h-2.5 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"
                  ></span>
                </div>
                <h4 class="text-sm font-bold text-zinc-100">Kompresi Tinggi</h4>
                <p class="text-xs text-zinc-400 mt-1 leading-relaxed">
                  Ukuran file paling ringkas. Sangat cocok untuk lampiran email dengan batasan ukuran kecil.
                </p>
              </div>
              <div class="mt-4 pt-3 border-t border-[#232836] text-[11px] font-mono text-zinc-500">
                Pengurangan estimasi: ~65% - 85%
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-2 flex justify-end">
          <button
            @click="compressPdf"
            :disabled="isProcessing"
            class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-black font-semibold text-sm rounded-lg flex items-center gap-2 shadow-lg shadow-amber-500/10 transition active:scale-95"
          >
            <Loader2 v-if="isProcessing" class="w-4 h-4 animate-spin" />
            <Minimize2 v-else class="w-4 h-4" />
            <span>{{ isProcessing ? 'Memproses Kompresi...' : 'Mulai Kompres Dokumen' }}</span>
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
import { Minimize2, FileText, X, Download, Loader2, AlertCircle } from 'lucide-vue-next';
import { formatBytes } from '@/Utils/format';

const emit = defineEmits(['operation-completed']);

const fileInputRef = ref(null);
const selectedFile = ref(null);
const compressionLevel = ref('medium');
const isDragging = ref(false);
const isProcessing = ref(false);
const errorMessage = ref('');
const result = ref(null);

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

function setPdfFile(file) {
  errorMessage.value = '';
  if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
    errorMessage.value = 'Harap pilih file dokumen berformat PDF (.pdf).';
    return;
  }
  selectedFile.value = file;
}

async function compressPdf() {
  if (!selectedFile.value) return;

  isProcessing.value = true;
  errorMessage.value = '';

  const formData = new FormData();
  formData.append('file', selectedFile.value);
  formData.append('level', compressionLevel.value);

  try {
    const res = await fetch('/pdf/compress', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: formData,
    });

    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Gagal mengompres PDF.');
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
  compressionLevel.value = 'medium';
}
</script>
