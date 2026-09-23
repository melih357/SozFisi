(() => {
    const speechButton = document.querySelector('[data-speech-button]');
    const target = document.querySelector('[data-speech-target]');
    const status = document.querySelector('[data-speech-status]');
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!speechButton || !target) return;

    if (!SpeechRecognition) {
        speechButton.disabled = true;
        if (status) status.textContent = 'Bu tarayıcı sesli girişi desteklemiyor.';
        return;
    }

    const recognition = new SpeechRecognition();
    recognition.lang = 'tr-TR';
    recognition.interimResults = true;
    recognition.continuous = false;
    let baseText = '';

    speechButton.addEventListener('click', () => {
        baseText = target.value.trim();
        recognition.start();
        speechButton.textContent = '● Dinleniyor…';
        if (status) status.textContent = 'Konuşmanızı Türkçe olarak algılıyorum.';
    });

    recognition.addEventListener('result', (event) => {
        const transcript = Array.from(event.results).map((result) => result[0].transcript).join(' ');
        target.value = [baseText, transcript].filter(Boolean).join(baseText ? ' ' : '');
    });

    recognition.addEventListener('end', () => {
        speechButton.textContent = '🎙️ Konuşarak anlat';
        if (status) status.textContent = 'Sesli giriş tamamlandı; metni kontrol edin.';
    });

    recognition.addEventListener('error', () => {
        speechButton.textContent = '🎙️ Konuşarak anlat';
        if (status) status.textContent = 'Mikrofona erişilemedi. Tarayıcı izinlerini kontrol edin.';
    });
})();
