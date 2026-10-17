import fs from 'fs';
import path from 'path';

const soundsDir = path.resolve('public/sounds');
if (!fs.existsSync(soundsDir)) {
    fs.mkdirSync(soundsDir, { recursive: true });
}

function createWavBuffer(samples, sampleRate = 22050) {
    const numChannels = 1;
    const bitsPerSample = 16;
    const byteRate = (sampleRate * numChannels * bitsPerSample) / 8;
    const blockAlign = (numChannels * bitsPerSample) / 8;
    const dataSize = samples.length * 2;
    const buffer = Buffer.alloc(44 + dataSize);

    // RIFF chunk descriptor
    buffer.write('RIFF', 0);
    buffer.writeUInt32LE(36 + dataSize, 4);
    buffer.write('WAVE', 8);

    // fmt sub-chunk
    buffer.write('fmt ', 12);
    buffer.writeUInt32LE(16, 16); // subchunk1size (16 for PCM)
    buffer.writeUInt16LE(1, 20);  // audioFormat (1 for PCM)
    buffer.writeUInt16LE(numChannels, 22);
    buffer.writeUInt32LE(sampleRate, 24);
    buffer.writeUInt32LE(byteRate, 28);
    buffer.writeUInt16LE(blockAlign, 32);
    buffer.writeUInt16LE(bitsPerSample, 34);

    // data sub-chunk
    buffer.write('data', 36);
    buffer.writeUInt32LE(dataSize, 40);

    let offset = 44;
    for (let i = 0; i < samples.length; i++) {
        let sample = Math.max(-1, Math.min(1, samples[i]));
        let intSample = sample < 0 ? sample * 32768 : sample * 32767;
        buffer.writeInt16LE(Math.floor(intSample), offset);
        offset += 2;
    }

    return buffer;
}

function generateChime(frequencies, durations, sampleRate = 22050) {
    let totalSamples = 0;
    durations.forEach(d => totalSamples += Math.floor(d * sampleRate));
    const samples = new Float32Array(totalSamples);
    
    let currentIndex = 0;
    for (let f = 0; f < frequencies.length; f++) {
        const freq = frequencies[f];
        const dur = durations[f];
        const numSamples = Math.floor(dur * sampleRate);

        for (let i = 0; i < numSamples; i++) {
            const t = i / sampleRate;
            // Decay envelope
            const envelope = Math.exp(-3 * (i / numSamples));
            // Fundamental + slight harmonic for bell warmth
            const tone = Math.sin(2 * Math.PI * freq * t) * 0.7 + Math.sin(4 * Math.PI * freq * t) * 0.3;
            samples[currentIndex++] = tone * envelope * 0.7;
        }
    }
    return createWavBuffer(samples, sampleRate);
}

// 1. new-order: double pleasant chime (D5 -> A5)
fs.writeFileSync(path.join(soundsDir, 'new-order.mp3'), generateChime([587.33, 880.00], [0.25, 0.45]));

// 2. ticket: crisp kitchen/bar ticket bell (A5 -> C6)
fs.writeFileSync(path.join(soundsDir, 'ticket.mp3'), generateChime([880.00, 1046.50], [0.2, 0.4]));

// 3. payment-success: joyful upward arpeggio (C5 -> E5 -> G5 -> C6)
fs.writeFileSync(path.join(soundsDir, 'payment-success.mp3'), generateChime([523.25, 659.25, 783.99, 1046.50], [0.12, 0.12, 0.15, 0.5]));

// 4. ready: soft gentle call (E5 -> C5)
fs.writeFileSync(path.join(soundsDir, 'ready.mp3'), generateChime([659.25, 523.25], [0.3, 0.5]));

// 5. alert: distinct dual pulse (440Hz / 350Hz)
fs.writeFileSync(path.join(soundsDir, 'alert.mp3'), generateChime([440.00, 349.23], [0.2, 0.35]));

console.log('Successfully generated all 5 UMCO House sound assets in public/sounds/');
