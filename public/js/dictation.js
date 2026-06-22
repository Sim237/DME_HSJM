/**
 * ═══════════════════════════════════════════════════════════════
 *  Module Dictée Vocale Dual-Engine — SimCare+ HSJM
 *
 *  Moteur 1 — VOSK  (WebSocket, port 2700)
 *    → Résultats partiels mot-à-mot en < 100 ms
 *    → Texte apparaît en temps réel pendant que vous parlez
 *
 *  Moteur 2 — WHISPER-SERVER (HTTP, port 9876)
 *    → Correction précise à chaque pause (~0,5 s)
 *    → Remplace le texte Vosk par une version plus exacte
 *
 *  Si Vosk est indisponible → mode Whisper seul (dégradé gracieux)
 * ═══════════════════════════════════════════════════════════════
 */
(function () {
    'use strict';

    /* ── URLs des serveurs ── */
    var _host       = window.location.hostname || '127.0.0.1';
    var API_WHISPER = (typeof BASE_URL !== 'undefined' ? BASE_URL : '/dme_hospital/')
                    + 'public/api/dictee.php';
    var WS_VOSK     = 'ws://' + _host + ':2700';

    /* ── Paramètres de détection de silence ── */
    var SILENCE_DELAY_MS  = 700;   /* délai avant envoi à Whisper */
    var SILENCE_THRESHOLD = 6;     /* seuil RMS silence (0-100)   */
    var MIN_AUDIO_MS      = 300;   /* ignorer clips < 300 ms       */

    var session = null;

    /* ══════════════════════════════════════════════════════════
       INITIALISATION
    ══════════════════════════════════════════════════════════ */
    function init() {
        var form = document.querySelector('.consultation-form');
        if (!form) return;
        // Toujours afficher les boutons micro — la vérification du contexte sécurisé
        // se fait au clic (startSession), pas ici. Cela permet au module de s'afficher
        // même sur HTTP (réseau local Ubuntu sans HTTPS configuré).
        attachAll(form);
    }

    function attachAll(container) {
        container.querySelectorAll(
            'textarea:not([data-dictee-init]):not([readonly]):not([disabled]):not([data-no-dictee])'
        ).forEach(function (ta) { attachField(ta); });

        container.querySelectorAll(
            'input[type="text"]:not([data-dictee-init]):not([readonly]):not([disabled]):not([data-no-dictee])'
        ).forEach(function (inp) {
            if (inp.getAttribute('role') === 'search') return;
            attachField(inp);
        });
    }

    /**
     * Appeler depuis l'extérieur pour attacher la dictée à un conteneur
     * nouvellement injecté dans le DOM (lignes médicaments, etc.).
     *   window.dicteeAttach(containerEl)
     */
    window.dicteeAttach = function (container) {
        if (container) attachAll(container);
    };

    /* ══════════════════════════════════════════════════════════
       ATTACHER LE BOUTON MIC
    ══════════════════════════════════════════════════════════ */
    function attachField(field) {
        field.setAttribute('data-dictee-init', '1');

        var isInput = field.tagName.toLowerCase() === 'input';
        var parent  = field.parentNode;
        var wrapper = document.createElement('div');
        wrapper.className = 'dictation-wrapper' + (isInput ? ' dictation-input-wrapper' : '');

        parent.insertBefore(wrapper, field);
        wrapper.appendChild(field);

        var status = document.createElement('span');
        status.className = 'dictee-status';
        wrapper.appendChild(status);

        var btn = document.createElement('button');
        btn.type      = 'button';
        btn.className = 'btn-dictee';
        btn.title     = 'Dicter';
        btn.setAttribute('aria-label', 'Démarrer / arrêter la dictée');
        btn.innerHTML = '<i class="bi bi-mic"></i>';
        wrapper.appendChild(btn);

        btn.addEventListener('click', function () { toggle(field, btn, status); });
    }

    /* ══════════════════════════════════════════════════════════
       BASCULE DÉMARRAGE / ARRÊT
    ══════════════════════════════════════════════════════════ */
    function toggle(field, btn, status) {
        if (session && session.btn !== btn) endSession(true);
        if (btn.classList.contains('recording')) {
            endSession(true);
        } else {
            startSession(field, btn, status);
        }
    }

    /* ══════════════════════════════════════════════════════════
       DÉMARRAGE DE SESSION
    ══════════════════════════════════════════════════════════ */
    function startSession(field, btn, status) {
        /* Vérification du contexte sécurisé (HTTPS ou localhost requis par les navigateurs) */
        var hasGetUserMedia = !!(
            navigator.mediaDevices && navigator.mediaDevices.getUserMedia
        );
        if (!hasGetUserMedia) {
            var isHttp = location.protocol === 'http:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1';
            var msg = isHttp
                ? 'La dictée nécessite HTTPS. Activez SSL sur votre serveur Ubuntu ou accédez via localhost.'
                : 'Votre navigateur ne supporte pas l\'accès au microphone. Utilisez Chrome ou Edge.';
            setStatus(status, '<span style="color:#dc2626">⚠ ' + msg + '</span>', true);
            setTimeout(function () { hideStatus(status); }, 6000);
            return;
        }
        navigator.mediaDevices.getUserMedia({ audio: true, video: false })
            .then(function (stream) {

                var ctx        = new (window.AudioContext || window.webkitAudioContext)();
                var source     = ctx.createMediaStreamSource(stream);
                var analyser   = ctx.createAnalyser();
                var processor  = ctx.createScriptProcessor(2048, 1, 1);
                var sampleRate = ctx.sampleRate;
                var minSamples = Math.round(sampleRate * MIN_AUDIO_MS / 1000);

                analyser.fftSize = 256;
                analyser.smoothingTimeConstant = 0.2;
                var freqData = new Uint8Array(analyser.frequencyBinCount);

                source.connect(analyser);
                source.connect(processor);
                processor.connect(ctx.destination);

                /* ── État textuel ── */
                var baseValue  = field.value;   /* texte confirmé (Whisper) */
                var voskFinal  = '';             /* dernier final Vosk (fallback) */
                var isSending  = false;

                /* ── Buffer Whisper ── */
                var whisperChunk = [];

                /* ── Connexion Vosk WebSocket ── */
                var vosk = null;
                try {
                    vosk = new WebSocket(WS_VOSK);
                    vosk.binaryType = 'arraybuffer';

                    vosk.onopen = function () {
                        /* OK, Vosk est prêt */
                    };

                    vosk.onmessage = function (e) {
                        if (!session) return;
                        try {
                            var data = JSON.parse(e.data);
                            var text = (data.text || '').trim();

                            if (data.type === 'partial' && text) {
                                /* Afficher le texte en temps réel */
                                var sep = baseValue.length > 0 && !/\s$/.test(baseValue) ? ' ' : '';
                                field.value = baseValue + sep + text;
                                field.dispatchEvent(new Event('input', { bubbles: true }));

                            } else if (data.type === 'final' && text) {
                                /* Conserver comme fallback si Whisper renvoie vide */
                                voskFinal = text;
                            }
                        } catch (ex) { /* ignore JSON mal formé */ }
                    };

                    vosk.onerror = function () { vosk = null; };
                    vosk.onclose = function () { /* normal */  };

                } catch (ex) {
                    vosk = null; /* Vosk indisponible — mode Whisper seul */
                }

                /* ── Collecte audio ── */
                processor.onaudioprocess = function (e) {
                    if (!session) return;
                    var float32 = new Float32Array(e.inputBuffer.getChannelData(0));

                    /* Whisper : accumuler en Float32 natif */
                    whisperChunk.push(float32);

                    /* Vosk : downsampler vers 16kHz et envoyer en Int16 */
                    if (vosk && vosk.readyState === WebSocket.OPEN) {
                        var int16 = downsampleToInt16(float32, sampleRate, 16000);
                        vosk.send(int16.buffer);
                    }
                };

                /* ── Détection de silence toutes les 60 ms ── */
                var silenceTimer   = null;
                var silenceInterval = setInterval(function () {
                    if (!session) { clearInterval(silenceInterval); return; }

                    analyser.getByteFrequencyData(freqData);
                    var sum = 0;
                    for (var i = 0; i < freqData.length; i++) sum += freqData[i];
                    var level = (sum / freqData.length) / 2.55;

                    if (level < SILENCE_THRESHOLD) {
                        if (!silenceTimer && !isSending && totalSamples(whisperChunk) >= minSamples) {
                            silenceTimer = setTimeout(function () {
                                silenceTimer = null;
                                var chunk = whisperChunk.splice(0);
                                if (totalSamples(chunk) < minSamples) return;
                                isSending = true;

                                /* Capturer le texte partiel Vosk actuel */
                                var partialSnapshot = field.value;

                                /*
                                 * Priorité : Vosk final (temps réel, déjà validé)
                                 * Fallback  : Whisper (si Vosk n'a rien reconnu)
                                 *
                                 * On capture voskFinal AVANT l'appel async à Whisper
                                 * pour ne pas utiliser un final d'un segment suivant.
                                 */
                                var voskCapture = voskFinal;
                                voskFinal = '';

                                if (voskCapture) {
                                    /* Vosk a reconnu quelque chose → l'utiliser directement */
                                    isSending = false;
                                    var sep = baseValue.length > 0 && !/\s$/.test(baseValue) ? ' ' : '';
                                    baseValue += sep + capitalize(voskCapture);
                                    field.value = baseValue;
                                    field.dispatchEvent(new Event('input', { bubbles: true }));
                                    if (field.setSelectionRange) {
                                        field.setSelectionRange(baseValue.length, baseValue.length);
                                    }
                                    restoreStatus(status, field);
                                } else {
                                    /* Vosk vide → envoyer à Whisper en fallback */
                                    sendToWhisper(chunk, sampleRate, function (whisperText) {
                                        isSending = false;
                                        /* Effacer tout voskFinal arrivé en retard pendant
                                           le traitement Whisper — évite la duplication */
                                        voskFinal = '';
                                        if (!session || session.field !== field) return;

                                        if (whisperText) {
                                            var sep = baseValue.length > 0 && !/\s$/.test(baseValue) ? ' ' : '';
                                            baseValue += sep + capitalize(whisperText);
                                            field.value = baseValue;
                                            field.dispatchEvent(new Event('input', { bubbles: true }));
                                            if (field.setSelectionRange) {
                                                field.setSelectionRange(baseValue.length, baseValue.length);
                                            }
                                        } else {
                                            /* Les deux vides → remettre la valeur confirmée */
                                            field.value = baseValue;
                                        }

                                        restoreStatus(status, field);
                                    }, status);
                                }
                            }, SILENCE_DELAY_MS);
                        }
                    } else {
                        if (silenceTimer) { clearTimeout(silenceTimer); silenceTimer = null; }
                    }
                }, 60);

                var autoStop = setTimeout(function () { if (session) endSession(true); }, 300000);

                session = {
                    stream         : stream,
                    ctx            : ctx,
                    source         : source,
                    processor      : processor,
                    vosk           : vosk,
                    whisperChunk   : whisperChunk,
                    sampleRate     : sampleRate,
                    minSamples     : minSamples,
                    silenceInterval: silenceInterval,
                    getTimer       : function () { return silenceTimer; },
                    clearTimer     : function () { if (silenceTimer) { clearTimeout(silenceTimer); silenceTimer = null; } },
                    autoStop       : autoStop,
                    btn            : btn,
                    status         : status,
                    field          : field,
                    getBase        : function () { return baseValue; },
                    setBase        : function (v) { baseValue = v; },
                    getVoskFinal   : function () { return voskFinal; },
                };

                /* UI */
                btn.classList.add('recording');
                btn.innerHTML = '<i class="bi bi-stop-circle-fill"></i>';
                btn.title     = 'Arrêter la dictée';

                var engineLabel = (vosk !== null) ? 'Vosk + Whisper' : 'Whisper';
                setStatus(status, '<span class="dictee-dot"></span>En écoute… <small style="opacity:.7">(' + engineLabel + ')</small>', true);
            })
            .catch(function (err) {
                var msg = err.name === 'NotAllowedError'
                    ? '🚫 Microphone refusé'
                    : '⚠ Micro indisponible : ' + err.message;
                setStatus(status, msg, true);
                setTimeout(function () { hideStatus(status); }, 3500);
            });
    }

    /* ══════════════════════════════════════════════════════════
       FIN DE SESSION
    ══════════════════════════════════════════════════════════ */
    function endSession(sendRemainder) {
        if (!session) return;
        var s = session;
        session = null;

        clearInterval(s.silenceInterval);
        s.clearTimer();
        clearTimeout(s.autoStop);

        /* Fermer Vosk */
        try { if (s.vosk) s.vosk.close(); } catch (e) {}

        /* Libérer audio */
        try { s.processor.disconnect(); } catch (e) {}
        try { s.source.disconnect();    } catch (e) {}
        try { s.stream.getTracks().forEach(function (t) { t.stop(); }); } catch (e) {}
        try { s.ctx.close(); } catch (e) {}

        var remaining = s.whisperChunk.splice(0);
        if (sendRemainder && totalSamples(remaining) >= s.minSamples) {
            s.btn.classList.remove('recording');
            s.btn.classList.add('transcribing');
            s.btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
            s.btn.disabled  = true;
            setStatus(s.status, '⏳ Finalisation…', true);

            /* Même priorité : Vosk final d'abord, Whisper si Vosk vide */
            var lastVosk = s.getVoskFinal();
            if (lastVosk) {
                var base = s.getBase();
                var sep  = base.length > 0 && !/\s$/.test(base) ? ' ' : '';
                s.field.value = base + sep + capitalize(lastVosk);
                s.field.dispatchEvent(new Event('input', { bubbles: true }));
                resetBtn(s.btn);
                hideStatus(s.status);
            } else {
                sendToWhisper(remaining, s.sampleRate, function (whisperText) {
                    var base = s.getBase();
                    if (whisperText) {
                        var sep = base.length > 0 && !/\s$/.test(base) ? ' ' : '';
                        s.field.value = base + sep + capitalize(whisperText);
                    } else {
                        s.field.value = base;
                    }
                    s.field.dispatchEvent(new Event('input', { bubbles: true }));
                    resetBtn(s.btn);
                    hideStatus(s.status);
                }, s.status);
            }
        } else {
            s.field.value = s.getBase();
            s.field.dispatchEvent(new Event('input', { bubbles: true }));
            resetBtn(s.btn);
            hideStatus(s.status);
        }
    }

    /* ══════════════════════════════════════════════════════════
       ENVOI À WHISPER-SERVER
    ══════════════════════════════════════════════════════════ */
    function sendToWhisper(chunks, sampleRate, onResult, status) {
        if (status) setStatus(status, '✦ correction…', true);
        var wav = encodePCMtoWAV(chunks, sampleRate);

        fetch(API_WHISPER, {
            method : 'POST',
            headers: { 'Content-Type': 'audio/wav' },
            body   : wav
        })
        .then(function (r) { return r.ok ? r.json() : Promise.reject('HTTP ' + r.status); })
        .then(function (data) {
            var text = data.error ? '' : (data.texte || '').trim();
            onResult(text);
        })
        .catch(function () { onResult(''); });
    }

    /* ══════════════════════════════════════════════════════════
       DOWNSAMPLING Float32 → Int16 (pour Vosk)
    ══════════════════════════════════════════════════════════ */
    function downsampleToInt16(float32, inputRate, outputRate) {
        if (inputRate === outputRate) {
            var out = new Int16Array(float32.length);
            for (var i = 0; i < float32.length; i++) {
                var s = Math.max(-1, Math.min(1, float32[i]));
                out[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
            }
            return out;
        }
        var ratio  = inputRate / outputRate;
        var length = Math.floor(float32.length / ratio);
        var result = new Int16Array(length);
        for (var j = 0; j < length; j++) {
            /* Interpolation linéaire */
            var pos = j * ratio;
            var idx = Math.floor(pos);
            var frac = pos - idx;
            var s0  = float32[idx]     || 0;
            var s1  = float32[idx + 1] || s0;
            var val = s0 + (s1 - s0) * frac;
            val = Math.max(-1, Math.min(1, val));
            result[j] = val < 0 ? val * 0x8000 : val * 0x7FFF;
        }
        return result;
    }

    /* ══════════════════════════════════════════════════════════
       ENCODAGE WAV PCM 16 bits mono (pour Whisper)
    ══════════════════════════════════════════════════════════ */
    function encodePCMtoWAV(chunks, sampleRate) {
        var total = totalSamples(chunks);
        var flat  = new Float32Array(total);
        var off   = 0;
        for (var i = 0; i < chunks.length; i++) { flat.set(chunks[i], off); off += chunks[i].length; }

        var dataBytes = total * 2;
        var buf  = new ArrayBuffer(44 + dataBytes);
        var view = new DataView(buf);

        writeStr(view,  0, 'RIFF');  view.setUint32( 4, 36 + dataBytes, true);
        writeStr(view,  8, 'WAVE');
        writeStr(view, 12, 'fmt ');  view.setUint32(16, 16,             true);
        view.setUint16(20,  1, true);
        view.setUint16(22,  1, true);
        view.setUint32(24, sampleRate,     true);
        view.setUint32(28, sampleRate * 2, true);
        view.setUint16(32,  2, true);
        view.setUint16(34, 16, true);
        writeStr(view, 36, 'data');  view.setUint32(40, dataBytes, true);

        var pos = 44;
        for (var k = 0; k < flat.length; k++) {
            var s = Math.max(-1, Math.min(1, flat[k]));
            view.setInt16(pos, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
            pos += 2;
        }
        return new Blob([buf], { type: 'audio/wav' });
    }

    function writeStr(view, offset, str) {
        for (var i = 0; i < str.length; i++) view.setUint8(offset + i, str.charCodeAt(i));
    }

    function totalSamples(chunks) {
        var n = 0; for (var i = 0; i < chunks.length; i++) n += chunks[i].length; return n;
    }

    /* ══════════════════════════════════════════════════════════
       UTILITAIRES UI
    ══════════════════════════════════════════════════════════ */
    function restoreStatus(status, field) {
        if (session && session.field === field) {
            setStatus(status, '<span class="dictee-dot"></span>En écoute…', true);
        } else {
            hideStatus(status);
        }
    }

    function resetBtn(btn) {
        btn.classList.remove('recording', 'transcribing');
        btn.innerHTML = '<i class="bi bi-mic"></i>';
        btn.title     = 'Dicter';
        btn.disabled  = false;
    }

    function setStatus(el, html, show) {
        el.innerHTML = html;
        if (show) el.classList.add('visible');
    }

    function hideStatus(el) { el.classList.remove('visible'); }
    function capitalize(s)  { return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }

    function showBanner(form, msg) {
        if (sessionStorage.getItem('dictee_banner_shown')) return;
        sessionStorage.setItem('dictee_banner_shown', '1');
        var b = document.createElement('div');
        b.className = 'dictee-unsupported-banner';
        b.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i><span>' + msg + '</span>';
        form.insertBefore(b, form.firstChild);
        setTimeout(function () { b.style.display = 'none'; }, 8000);
    }

    /* ══════════════════════════════════════════════════════════
       DÉMARRAGE + MUTATION OBSERVER
    ══════════════════════════════════════════════════════════ */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.querySelector('.consultation-form');
        if (!form) return;
        new MutationObserver(function (m) {
            if (m.some(function (x) { return x.addedNodes.length > 0; })) attachAll(form);
        }).observe(form, { childList: true, subtree: true });
    });

})();
