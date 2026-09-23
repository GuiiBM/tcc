/* =====================================================================
 * player.js - Motor do player do Ressonance.
 *
 * Um único <audio> vive no rodapé do casco do app. Como a navegação entre
 * páginas troca só o conteúdo (#app-content), a música continua tocando.
 * Em páginas que recarregam por completo (admin), o estado (fila, posição,
 * volume...) é salvo no localStorage e restaurado ao abrir a página.
 * ===================================================================== */
(function () {
    'use strict';

    const STORAGE_KEY = 'ressonance.player.v2';
    const EPISODES_KEY = 'ressonance.episodios';
    const SPEEDS = [0.75, 1, 1.25, 1.5, 1.75, 2];
    const $ = (id) => document.getElementById(id);

    // ===================== Utilitários compartilhados =====================
    const RS = window.RS = window.RS || {};

    RS.fmt = function (seconds) {
        seconds = Math.max(0, Math.floor(Number(seconds) || 0));
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return h > 0
            ? `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
            : `${m}:${String(s).padStart(2, '0')}`;
    };

    RS.esc = function (text) {
        return String(text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    };

    RS.icon = function (name, cls) {
        return `<svg class="icon${cls ? ' ' + cls : ''}" aria-hidden="true"><use href="#i-${name}"></use></svg>`;
    };

    // Chamada à API: GET quando não há dados; POST (JSON ou FormData) caso
    // contrário. Sempre manda o cabeçalho exigido pelo servidor contra CSRF.
    RS.api = async function (url, data, options = {}) {
        const init = { headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' }, credentials: 'same-origin' };
        if (options.signal) init.signal = options.signal;
        if (data !== undefined) {
            init.method = 'POST';
            if (data instanceof FormData) {
                init.body = data;
            } else {
                init.headers['Content-Type'] = 'application/json';
                init.body = JSON.stringify(data);
            }
        }
        const response = await fetch(url, init);
        let json;
        try {
            json = await response.json();
        } catch (e) {
            throw new Error('Resposta inválida do servidor');
        }
        if (!response.ok || json.success === false) {
            const error = new Error(json.message || 'Algo deu errado');
            error.status = response.status;
            throw error;
        }
        return json;
    };

    RS.storage = {
        get(key, fallback) {
            try {
                const raw = localStorage.getItem(key);
                return raw === null ? fallback : JSON.parse(raw);
            } catch (e) {
                return fallback;
            }
        },
        set(key, value) {
            try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* armazenamento indisponível */ }
        },
    };

    function clamp(n, min, max) { return Math.min(max, Math.max(min, n)); }

    function shuffleArray(list) {
        for (let i = list.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [list[i], list[j]] = [list[j], list[i]];
        }
        return list;
    }

    function absoluteUrl(path) {
        try { return new URL(path, document.baseURI).href; } catch (e) { return path; }
    }

    // ===================== Motor =====================
    class Player {
        constructor(audio) {
            this.audio = audio;
            this.queue = [];
            this.original = null; // ordem original (uids) enquanto o aleatório está ligado
            this.index = -1;
            this.shuffle = false;
            this.repeat = 'off'; // off | all | one
            this.volume = 0.7;
            this.muted = false;
            this.podcastSpeed = 1;
            this.listeners = {};
            this.listened = 0;
            this.lastPos = 0;
            this.historyLogged = false;
            this.viewLogged = false;
            this._saveAt = 0;

            this.bindAudio();
        }

        on(event, fn) { (this.listeners[event] = this.listeners[event] || []).push(fn); }
        emit(event, data) { (this.listeners[event] || []).forEach((fn) => fn(data)); }

        get current() { return this.queue[this.index] || null; }
        get isPodcast() { return !!this.current && this.current.kind === 'episodio'; }
        get duration() {
            const d = this.audio.duration;
            return isFinite(d) && d > 0 ? d : (this.current ? this.current.duration || 0 : 0);
        }

        newUid() { return Math.random().toString(36).slice(2, 9) + Date.now().toString(36).slice(-4); }
        prepare(track) { return Object.assign({}, track, { uid: this.newUid() }); }

        // ----- Fila -----
        playList(tracks, startIndex = 0) {
            if (!tracks || !tracks.length) return;
            this.queue = tracks.map((t) => this.prepare(t));
            this.original = null;
            this.index = clamp(startIndex, 0, this.queue.length - 1);
            if (this.shuffle) this.applyShuffle();
            this.load({ autoplay: true });
            this.emit('queue');
        }

        playNext(track) {
            const item = this.prepare(track);
            if (!this.current) { this.playList([track]); return; }
            this.queue.splice(this.index + 1, 0, item);
            if (this.original) {
                const pos = this.original.indexOf(this.current.uid);
                this.original.splice(pos + 1, 0, item.uid);
            }
            this.emit('queue');
            this.save();
        }

        addToQueue(track) {
            const item = this.prepare(track);
            if (!this.current) { this.playList([track]); return; }
            this.queue.push(item);
            if (this.original) this.original.push(item.uid);
            this.emit('queue');
            this.save();
        }

        jumpTo(i) {
            if (i < 0 || i >= this.queue.length) return;
            this.index = i;
            this.load({ autoplay: true });
            this.emit('queue');
        }

        removeAt(i) {
            if (i <= this.index || i >= this.queue.length) return;
            this.queue.splice(i, 1);
            this.emit('queue');
            this.save();
        }

        // Move um item da parte "a seguir" (índices absolutos da fila).
        move(from, to) {
            if (from === to || from <= this.index || to <= this.index) return;
            const [item] = this.queue.splice(from, 1);
            this.queue.splice(to, 0, item);
            // A reordenação manual vira a nova ordem "original" também.
            if (this.original) this.original = this.queue.map((t) => t.uid);
            this.emit('queue');
            this.save();
        }

        clearUpcoming() {
            this.queue = this.current ? this.queue.slice(0, this.index + 1) : [];
            if (this.original) this.original = this.queue.map((t) => t.uid);
            this.emit('queue');
            this.save();
        }

        applyShuffle() {
            const current = this.current;
            this.original = this.queue.map((t) => t.uid);
            const rest = shuffleArray(this.queue.filter((_, i) => i !== this.index));
            this.queue = current ? [current, ...rest] : rest;
            this.index = current ? 0 : -1;
        }

        restoreOrder() {
            const current = this.current;
            const byUid = new Map(this.queue.map((t) => [t.uid, t]));
            const ordered = [];
            (this.original || []).forEach((uid) => {
                if (byUid.has(uid)) { ordered.push(byUid.get(uid)); byUid.delete(uid); }
            });
            byUid.forEach((t) => ordered.push(t));
            this.queue = ordered;
            this.index = current ? this.queue.findIndex((t) => t.uid === current.uid) : -1;
            this.original = null;
        }

        setShuffle(on) {
            if (on === this.shuffle) return;
            this.shuffle = on;
            if (this.queue.length) {
                if (on) this.applyShuffle(); else this.restoreOrder();
            }
            this.emit('modes');
            this.emit('queue');
            this.save();
        }

        cycleRepeat() {
            this.repeat = { off: 'all', all: 'one', one: 'off' }[this.repeat];
            this.emit('modes');
            this.save();
        }

        // ----- Reprodução -----
        chooseSource(track) {
            if (!track.srcLow) return track.src;
            const pref = (window.APP && APP.usuario && APP.usuario.qualidade) || RS.storage.get('ressonance.qualidade', 'auto');
            if (pref === 'baixa') return track.srcLow;
            if (pref === 'alta') return track.src;
            const conn = navigator.connection;
            if (conn && (conn.saveData || /(^|-)2g|3g/.test(conn.effectiveType || ''))) return track.srcLow;
            return track.src;
        }

        load({ autoplay = false, startTime = null } = {}) {
            const track = this.current;
            if (!track) { this.reset(); return; }

            this.audio.src = this.chooseSource(track);
            const rate = track.kind === 'episodio' ? this.podcastSpeed : 1;
            this.audio.defaultPlaybackRate = rate;
            this.audio.playbackRate = rate;

            let resumeAt = startTime;
            if (resumeAt === null && track.kind === 'episodio') {
                const saved = RS.storage.get(EPISODES_KEY, {})[track.id];
                if (saved && saved.t > 5 && !saved.done) resumeAt = saved.t;
            }
            if (resumeAt) {
                this.audio.addEventListener('loadedmetadata', () => {
                    this.audio.currentTime = Math.min(resumeAt, (this.audio.duration || resumeAt) - 1);
                }, { once: true });
            }

            this.listened = 0;
            this.lastPos = resumeAt || 0;
            this.linkRenewed = false;
            this.resumeTime = resumeAt || 0;
            this.historyLogged = false;
            this.viewLogged = false;

            this.emit('track', track);
            this.updateMediaSession();
            if (autoplay) this.play();
            this.save();
        }

        play() {
            this.wantPlay = true;
            if (!this.audio.src) return;
            const promise = this.audio.play();
            if (promise) {
                promise.catch((err) => {
                    if (err && err.name === 'NotAllowedError') return; // autoplay bloqueado: fica pausado
                    if (err && err.name === 'AbortError') return;
                    this.emit('error', 'Não foi possível tocar este áudio.');
                });
            }
        }

        pause() { this.wantPlay = false; this.audio.pause(); }

        toggle() {
            if (!this.current) return;
            if (this.audio.paused) this.play(); else this.pause();
        }

        next(auto = false) {
            if (!this.queue.length) return;
            if (this.index < this.queue.length - 1) {
                this.index++;
            } else if (this.repeat === 'all') {
                this.index = 0;
            } else {
                // Fim da fila: para no começo da última faixa.
                this.pause();
                this.audio.currentTime = 0;
                if (!auto) this.emit('message', 'Fim da fila');
                return;
            }
            this.load({ autoplay: true });
            this.emit('queue');
        }

        prev() {
            if (!this.queue.length) return;
            if (this.audio.currentTime > 3 || (this.index === 0 && this.repeat !== 'all')) {
                this.seek(0);
                return;
            }
            this.index = this.index > 0 ? this.index - 1 : this.queue.length - 1;
            this.load({ autoplay: true });
            this.emit('queue');
        }

        seek(seconds) {
            if (!this.current || !this.duration) return;
            this.audio.currentTime = clamp(seconds, 0, this.duration - 0.05);
            this.lastPos = this.audio.currentTime;
        }

        skip(delta) { this.seek(this.audio.currentTime + delta); }

        setVolume(v) {
            this.volume = clamp(v, 0, 1);
            this.muted = this.volume === 0 ? this.muted : false;
            this.applyVolume();
        }

        toggleMute() {
            if (this.muted || this.volume === 0) {
                this.muted = false;
                if (this.volume === 0) this.volume = 0.5;
            } else {
                this.muted = true;
            }
            this.applyVolume();
        }

        applyVolume() {
            this.audio.volume = this.volume;
            this.audio.muted = this.muted;
            this.emit('volume');
            this.save();
        }

        setSpeed(rate) {
            this.podcastSpeed = rate;
            if (this.isPodcast) this.audio.playbackRate = rate;
            this.emit('speed');
            this.save();
        }

        cycleSpeed(direction = 1) {
            const i = SPEEDS.indexOf(this.podcastSpeed);
            const next = SPEEDS[(i + direction + SPEEDS.length) % SPEEDS.length];
            this.setSpeed(next);
        }

        // Tira a música do player: volta ao estado de quem nunca tocou nada
        // (sem fila, sem faixa, player escondido no celular). Mantém volume,
        // aleatório e repetir. Devolve um "retrato" para poder desfazer.
        eject() {
            if (!this.current) return null;
            const snapshot = {
                queue: this.queue, index: this.index, original: this.original,
                time: this.audio.currentTime || 0, playing: !this.audio.paused,
            };
            this.wantPlay = false;
            this.original = null;
            this.listSource = null;
            this.contextUrl = null;
            this.reset();
            if ('mediaSession' in navigator) {
                try { navigator.mediaSession.playbackState = 'none'; } catch (e) { /* sem suporte */ }
            }
            return snapshot;
        }

        undoEject(snapshot) {
            if (!snapshot || this.current) return;
            this.queue = snapshot.queue;
            this.index = snapshot.index;
            this.original = snapshot.original;
            this.load({ autoplay: snapshot.playing, startTime: snapshot.time });
            this.emit('queue');
        }

        reset() {
            this.audio.pause();
            this.audio.removeAttribute('src');
            this.audio.load();
            this.queue = [];
            this.index = -1;
            this.emit('track', null);
            this.emit('queue');
            this.save();
        }

        // ----- Eventos do <audio> -----
        bindAudio() {
            const a = this.audio;
            a.addEventListener('play', () => this.emit('state'));
            a.addEventListener('pause', () => { this.emit('state'); this.save(); });
            a.addEventListener('playing', () => {
                this.emit('state');
                const t = this.current;
                if (t && t.kind === 'musica' && !this.viewLogged) {
                    this.viewLogged = true;
                    RS.api('api/visualizacao.php', { musica_id: t.id }).catch(() => {});
                }
            });
            a.addEventListener('ended', () => this.onEnded());
            a.addEventListener('loadedmetadata', () => this.onMetadata());
            a.addEventListener('durationchange', () => this.emit('time'));
            a.addEventListener('progress', () => this.emit('buffer'));
            a.addEventListener('seeked', () => { this.lastPos = a.currentTime; });
            a.addEventListener('error', () => {
                if (!a.getAttribute('src')) return;
                // Os links de áudio são temporários: se expirou (ou a sessão
                // mudou), pede um link novo e continua de onde estava.
                if (!this.linkRenewed && /stream\.php/.test(a.src)) {
                    this.renewLink();
                    return;
                }
                this.emit('error', 'Não foi possível carregar este áudio.');
            });
            a.addEventListener('timeupdate', () => this.onTime());
            window.addEventListener('pagehide', () => this.save(true));
        }

        async renewLink() {
            const t = this.current;
            if (!t) return;
            this.linkRenewed = true;
            const position = this.audio.currentTime > 0 ? this.audio.currentTime : (this.resumeTime || 0);
            const resume = this.wantPlay;
            try {
                const data = await RS.api(`api/stream.php?tipo=${t.kind}&id=${t.id}`);
                if (this.current !== t) return;
                t.src = data.src;
                t.srcLow = data.srcLow;
                this.audio.src = this.chooseSource(t);
                const rate = t.kind === 'episodio' ? this.podcastSpeed : 1;
                this.audio.defaultPlaybackRate = rate;
                this.audio.playbackRate = rate;
                this.audio.addEventListener('loadedmetadata', () => {
                    if (position) this.audio.currentTime = position;
                    this.lastPos = position;
                    if (resume) this.play();
                }, { once: true });
                this.save();
            } catch (e) {
                this.emit('error', 'Não foi possível carregar este áudio.');
            }
        }

        onEnded() {
            this.countListening(true);
            if (this.repeat === 'one' && !this.isPodcast) {
                this.listened = 0;
                this.historyLogged = false;
                this.viewLogged = false;
                this.seek(0);
                this.play();
                return;
            }
            this.next(true);
        }

        onMetadata() {
            const t = this.current;
            this.emit('time');
            if (!t || !isFinite(this.audio.duration)) return;
            const real = Math.round(this.audio.duration);
            if (!t.duration || Math.abs(t.duration - real) > 2) {
                const hadNone = !t.duration;
                t.duration = real;
                this.emit('duration', t);
                if (hadNone) {
                    RS.api('api/duracao.php', { tipo: t.kind, id: t.id, duracao: real }).catch(() => {});
                }
            }
            this.updatePositionState();
        }

        onTime() {
            // Última posição válida (para retomar se o link do áudio expirar).
            if (this.audio.currentTime > 0 && !this.audio.seeking) this.resumeTime = this.audio.currentTime;
            this.countListening(false);
            this.emit('time');
            const now = Date.now();
            if (now - this._saveAt > 3000) {
                this._saveAt = now;
                this.save();
                this.saveEpisodeProgress();
                this.updatePositionState();
            }
        }

        // Soma só o tempo realmente ouvido (ignora saltos da barra) para
        // registrar no histórico apenas faixas ouvidas por completo (>= 90%).
        countListening(ended) {
            const a = this.audio;
            const delta = a.currentTime - this.lastPos;
            if (!a.paused && delta > 0 && delta < 3) this.listened += delta;
            this.lastPos = a.currentTime;
            const t = this.current;
            const dur = this.duration;
            if (!t || !dur || this.historyLogged) return;
            if (this.listened >= dur * 0.9 || (ended && this.listened >= dur * 0.85)) {
                this.historyLogged = true;
                if (t.kind === 'episodio') this.saveEpisodeProgress(true);
                this.emit('completed', t);
                if (window.APP && APP.logado) {
                    RS.api('api/historico.php', { tipo: t.kind, id: t.id }).catch(() => {});
                }
            }
        }

        saveEpisodeProgress(done = false) {
            const t = this.current;
            if (!t || t.kind !== 'episodio') return;
            const all = RS.storage.get(EPISODES_KEY, {});
            const previous = all[t.id] || {};
            all[t.id] = { t: Math.floor(this.audio.currentTime), d: Math.floor(this.duration), done: done || previous.done || false };
            RS.storage.set(EPISODES_KEY, all);
        }

        // ----- Media Session (tela de bloqueio / teclas de mídia) -----
        updateMediaSession() {
            if (!('mediaSession' in navigator)) return;
            const t = this.current;
            if (!t) { navigator.mediaSession.metadata = null; return; }
            try {
                navigator.mediaSession.metadata = new MediaMetadata({
                    title: t.title,
                    artist: t.artist,
                    album: t.album || '',
                    artwork: [{ src: absoluteUrl(t.cover), sizes: '512x512' }],
                });
            } catch (e) { /* navegador sem suporte completo */ }
        }

        updatePositionState() {
            if (!('mediaSession' in navigator) || !navigator.mediaSession.setPositionState) return;
            const d = this.audio.duration;
            if (!isFinite(d) || d <= 0) return;
            try {
                navigator.mediaSession.setPositionState({ duration: d, playbackRate: this.audio.playbackRate || 1, position: Math.min(this.audio.currentTime, d) });
            } catch (e) { /* ignorar */ }
        }

        bindMediaSession() {
            if (!('mediaSession' in navigator)) return;
            const ms = navigator.mediaSession;
            const set = (action, fn) => { try { ms.setActionHandler(action, fn); } catch (e) { /* ação não suportada */ } };
            set('play', () => this.play());
            set('pause', () => this.pause());
            set('previoustrack', () => this.prev());
            set('nexttrack', () => this.next());
            set('seekbackward', (d) => this.skip(-(d.seekOffset || (this.isPodcast ? 15 : 10))));
            set('seekforward', (d) => this.skip(d.seekOffset || (this.isPodcast ? 15 : 10)));
            set('seekto', (d) => this.seek(d.seekTime));
        }

        // ----- Persistência -----
        save(force) {
            if (!force && this._restoring) return;
            let queue = this.queue;
            let index = this.index;
            // Limita o tamanho salvo mantendo a faixa atual e as próximas.
            if (queue.length > 300) {
                const start = Math.max(0, index - 20);
                queue = queue.slice(start, start + 300);
                index -= start;
            }
            const uids = new Set(queue.map((t) => t.uid));
            RS.storage.set(STORAGE_KEY, {
                queue, index,
                original: this.original ? this.original.filter((u) => uids.has(u)) : null,
                time: this.audio.currentTime || 0,
                paused: this.audio.paused,
                shuffle: this.shuffle,
                repeat: this.repeat,
                volume: this.volume,
                muted: this.muted,
                podcastSpeed: this.podcastSpeed,
            });
        }

        restore() {
            const s = RS.storage.get(STORAGE_KEY, null);
            if (!s) { this.applyVolume(); return; }
            this.shuffle = !!s.shuffle;
            this.repeat = ['off', 'all', 'one'].includes(s.repeat) ? s.repeat : 'off';
            this.volume = typeof s.volume === 'number' ? clamp(s.volume, 0, 1) : 0.7;
            this.muted = !!s.muted;
            this.podcastSpeed = SPEEDS.includes(s.podcastSpeed) ? s.podcastSpeed : 1;
            this.applyVolume();
            this.emit('modes');
            this.emit('speed');

            if (Array.isArray(s.queue) && s.queue.length && s.index >= 0 && s.index < s.queue.length) {
                this._restoring = true;
                this.queue = s.queue;
                this.original = s.original || null;
                this.index = s.index;
                this.load({ autoplay: false, startTime: s.time || 0 });
                this._restoring = false;
                this.emit('queue');
                if (!s.paused) this.play();
            }
        }
    }

    // ===================== Interface =====================
    class PlayerUI {
        constructor(player) {
            this.p = player;
            this.root = $('player');
            this.showRemaining = RS.storage.get('ressonance.tempoRestante', true);
            this.dragging = false;
            this.lyrics = { trackKey: null, lines: [], synced: false, active: -1, userScrollUntil: 0 };
            this.lyricsCache = {};
            this.panelMode = null;

            this.bindControls();
            this.bindScrubber();
            this.bindQueueSorting();
            this.bindLyricsScroll();

            player.on('track', (t) => this.renderTrack(t));
            player.on('state', () => this.renderState());
            player.on('time', () => this.renderTime());
            player.on('buffer', () => this.renderBuffer());
            player.on('modes', () => this.renderModes());
            player.on('volume', () => this.renderVolume());
            player.on('speed', () => this.renderSpeed());
            player.on('queue', () => this.renderQueue());
            player.on('duration', (t) => this.renderDuration(t));
            player.on('error', (msg) => RS.toast && RS.toast(msg, 'error'));
            player.on('message', (msg) => RS.toast && RS.toast(msg));
        }

        bindControls() {
            const p = this.p;
            $('btnPlay').addEventListener('click', () => p.toggle());
            $('btnMiniPlay').addEventListener('click', (e) => { e.stopPropagation(); p.toggle(); });
            $('btnNext').addEventListener('click', () => p.next());
            $('btnPrev').addEventListener('click', () => p.prev());
            $('btnShuffle').addEventListener('click', () => p.setShuffle(!p.shuffle));
            $('btnRepeat').addEventListener('click', () => p.cycleRepeat());
            $('btnBack15').addEventListener('click', () => p.skip(-15));
            $('btnFwd15').addEventListener('click', () => p.skip(15));
            $('btnSpeed').addEventListener('click', () => p.cycleSpeed(1));
            $('btnMute').addEventListener('click', () => p.toggleMute());
            document.querySelectorAll('[data-close-player]').forEach((b) => b.addEventListener('click', (e) => {
                e.stopPropagation();
                this.close();
            }));
            $('volumeSlider').addEventListener('input', (e) => p.setVolume(e.target.value / 100));
            $('timeRight').addEventListener('click', () => {
                this.showRemaining = !this.showRemaining;
                RS.storage.set('ressonance.tempoRestante', this.showRemaining);
                this.renderTime();
            });
            // No celular, tocar no minicard abre o player em tela cheia.
            this.root.addEventListener('click', (e) => {
                if (!window.matchMedia('(max-width: 768px)').matches) return;
                if (this.root.classList.contains('is-expanded') || this.root.classList.contains('is-empty')) return;
                if (e.target.closest('button, a, input')) return;
                this.expand(true);
            });
        }

        close() {
            if (this.root.classList.contains('is-expanded')) {
                this.expand(false);
                if (history.state && history.state.playerExpanded) history.back();
            }
            this.closePanel();
            const snapshot = this.p.eject();
            if (snapshot && RS.toast) {
                RS.toast('Player fechado', 'info', { label: 'Desfazer', onClick: () => this.p.undoEject(snapshot) });
            }
        }

        expand(on) {
            this.root.classList.toggle('is-expanded', on);
            document.body.classList.toggle('player-expanded', on);
            if (on) history.pushState(Object.assign({}, history.state, { playerExpanded: true }), '');
        }

        // ----- Barra de progresso (clicar e arrastar) -----
        bindScrubber() {
            const scr = $('scrubber');
            const tooltip = $('scrubTooltip');
            const ratio = (e) => {
                const r = scr.getBoundingClientRect();
                return clamp((e.clientX - r.left) / r.width, 0, 1);
            };
            const preview = (r) => {
                const d = this.p.duration;
                this.setProgress(r);
                $('timeElapsed').textContent = RS.fmt(r * d);
                tooltip.textContent = RS.fmt(r * d);
                tooltip.style.left = `${r * 100}%`;
            };

            scr.addEventListener('pointerdown', (e) => {
                if (!this.p.current || !this.p.duration || e.button > 0) return;
                this.dragging = true;
                scr.setPointerCapture(e.pointerId);
                scr.classList.add('is-dragging');
                tooltip.hidden = false;
                preview(ratio(e));
                e.preventDefault();
            });
            scr.addEventListener('pointermove', (e) => {
                if (this.dragging) {
                    preview(ratio(e));
                } else if (e.pointerType === 'mouse' && this.p.duration) {
                    const r = ratio(e);
                    tooltip.hidden = false;
                    tooltip.textContent = RS.fmt(r * this.p.duration);
                    tooltip.style.left = `${r * 100}%`;
                }
            });
            scr.addEventListener('pointerleave', () => { if (!this.dragging) tooltip.hidden = true; });
            const finish = (e, commit) => {
                if (!this.dragging) return;
                this.dragging = false;
                scr.classList.remove('is-dragging');
                tooltip.hidden = true;
                if (commit) this.p.seek(ratio(e) * this.p.duration);
                this.renderTime();
            };
            scr.addEventListener('pointerup', (e) => finish(e, true));
            scr.addEventListener('pointercancel', (e) => finish(e, false));
            scr.addEventListener('keydown', (e) => {
                const step = this.p.isPodcast ? 15 : 5;
                const keys = { ArrowLeft: -step, ArrowDown: -step, ArrowRight: step, ArrowUp: step };
                if (keys[e.key] !== undefined) {
                    this.p.skip(keys[e.key]);
                } else if (e.key === 'Home') {
                    this.p.seek(0);
                } else if (e.key === 'End') {
                    this.p.seek(this.p.duration - 1);
                } else {
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
            });
        }

        setProgress(ratio) {
            const pct = `${clamp(ratio, 0, 1) * 100}%`;
            $('scrubFill').style.width = pct;
            $('scrubThumb').style.left = pct;
            $('miniProgress').style.width = pct;
        }

        // ----- Renderização -----
        renderTrack(t) {
            const root = this.root;
            root.classList.toggle('is-empty', !t);
            root.classList.toggle('is-podcast', !!t && t.kind === 'episodio');
            document.body.classList.toggle('has-track', !!t);

            const title = $('playerTitle');
            const artist = $('playerArtist');
            const like = $('playerLike');
            const dislike = $('playerDislike');
            if (!t) {
                title.textContent = 'Nada tocando';
                artist.textContent = 'Escolha uma música';
                title.removeAttribute('href');
                artist.removeAttribute('href');
                $('playerCover').src = 'Componentes/icones/icone.png';
                like.disabled = true;
                dislike.disabled = true;
                this.resetReaction();
                this.setProgress(0);
            } else {
                title.textContent = t.title;
                artist.textContent = t.artist;
                title.title = t.title;
                if (t.kind === 'episodio') {
                    title.href = `podcast.php?id=${t.podcastId}`;
                    artist.href = `podcast.php?id=${t.podcastId}`;
                } else {
                    title.href = t.albumId ? `album.php?id=${t.albumId}` : `artista.php?id=${t.artistId}`;
                    artist.href = `artista.php?id=${t.artistId}`;
                }
                $('playerCover').src = t.cover || 'Componentes/icones/icone.png';
                const isSong = t.kind === 'musica';
                like.disabled = !isSong;
                dislike.disabled = !isSong || !(window.APP && APP.logado);
                // Zera curtir/"não gostei" antes de carregar o estado da nova faixa,
                // para não herdar a marcação da anterior.
                this.resetReaction();
                like.dataset.likeId = isSong ? t.id : '';
                if (RS.syncLikes) RS.syncLikes();
                if (isSong && RS.loadReaction) RS.loadReaction(t.id);
            }
            this.renderState();
            this.renderTime();
            this.renderBuffer();
            this.renderSpeed();
            this.markPlayingRows();
            if (this.panelMode === 'lyrics') this.loadLyrics();
            document.getElementById('btnLyrics').disabled = !t || t.kind !== 'musica';
        }

        // Limpa a marcação de curtida e de "não gostei" do player.
        resetReaction() {
            const like = $('playerLike');
            const dislike = $('playerDislike');
            like.dataset.likeId = '';
            like.classList.remove('is-liked');
            like.setAttribute('aria-pressed', 'false');
            dislike.classList.remove('is-active');
            dislike.setAttribute('aria-pressed', 'false');
            $('dislikeCount').textContent = '';
            if (RS.syncLikes) RS.syncLikes();
        }

        renderState() {
            const playing = !this.p.audio.paused && !!this.p.current;
            this.root.classList.toggle('is-playing', playing);
            document.body.classList.toggle('is-playing', playing);
            const label = playing ? 'Pausar' : 'Tocar';
            $('btnPlay').setAttribute('aria-label', label);
            $('btnMiniPlay').setAttribute('aria-label', label);
            const noTrack = !this.p.current;
            ['btnPlay', 'btnNext', 'btnPrev', 'btnBack15', 'btnFwd15', 'btnMiniPlay'].forEach((id) => { $(id).disabled = noTrack; });
            this.markPlayingRows();
        }

        renderTime() {
            if (this.dragging) return;
            const d = this.p.duration;
            const c = this.p.audio.currentTime || 0;
            this.setProgress(d ? c / d : 0);
            $('timeElapsed').textContent = RS.fmt(c);
            $('timeRight').textContent = this.showRemaining ? `-${RS.fmt(Math.max(0, d - c))}` : RS.fmt(d);
            const scr = $('scrubber');
            scr.setAttribute('aria-valuemax', String(Math.floor(d)));
            scr.setAttribute('aria-valuenow', String(Math.floor(c)));
            scr.setAttribute('aria-valuetext', `${RS.fmt(c)} de ${RS.fmt(d)}`);
            if (this.panelMode === 'lyrics') this.syncLyrics();
        }

        renderBuffer() {
            const a = this.p.audio;
            const d = this.p.duration;
            let end = 0;
            if (d && a.buffered.length) {
                for (let i = 0; i < a.buffered.length; i++) {
                    if (a.buffered.start(i) <= a.currentTime + 0.5) end = Math.max(end, a.buffered.end(i));
                }
            }
            $('scrubBuffer').style.width = d ? `${clamp(end / d, 0, 1) * 100}%` : '0%';
        }

        renderDuration(t) {
            document.querySelectorAll(`[data-duration-for="${t.id}"]`).forEach((el) => { el.textContent = RS.fmt(t.duration); });
            this.renderTime();
        }

        renderModes() {
            const s = $('btnShuffle');
            s.classList.toggle('is-active', this.p.shuffle);
            s.setAttribute('aria-pressed', String(this.p.shuffle));
            s.setAttribute('aria-label', this.p.shuffle ? 'Desativar modo aleatório' : 'Ativar modo aleatório');

            const r = $('btnRepeat');
            const labels = { off: 'Repetir: desligado', all: 'Repetir: toda a fila', one: 'Repetir: esta música' };
            r.classList.toggle('is-active', this.p.repeat !== 'off');
            r.classList.toggle('is-one', this.p.repeat === 'one');
            r.setAttribute('aria-pressed', String(this.p.repeat !== 'off'));
            r.setAttribute('aria-label', labels[this.p.repeat]);
            r.title = `${labels[this.p.repeat]} (R)`;
        }

        renderVolume() {
            const v = this.p.muted ? 0 : this.p.volume;
            const slider = $('volumeSlider');
            slider.value = Math.round(v * 100);
            slider.style.setProperty('--fill', `${v * 100}%`);
            const level = v === 0 ? 'off' : v < 0.34 ? 'low' : v < 0.67 ? 'mid' : 'high';
            const btn = $('btnMute');
            btn.dataset.level = level;
            btn.setAttribute('aria-label', v === 0 ? 'Ativar som' : 'Silenciar');
        }

        renderSpeed() {
            const b = $('btnSpeed');
            b.textContent = `${String(this.p.podcastSpeed).replace('.', ',')}x`;
            b.classList.toggle('is-active', this.p.podcastSpeed !== 1);
        }

        // Destaca a linha/cartão da faixa que está tocando em qualquer lista.
        markPlayingRows() {
            const t = this.p.current;
            const key = t ? `${t.kind}:${t.id}` : null;
            const playing = !this.p.audio.paused;
            document.querySelectorAll('[data-track-key].is-current').forEach((el) => {
                if (el.dataset.trackKey !== key) el.classList.remove('is-current', 'is-playing');
            });
            if (key) {
                document.querySelectorAll(`[data-track-key="${key}"]`).forEach((el) => {
                    el.classList.add('is-current');
                    el.classList.toggle('is-playing', playing);
                });
            }
            // Botão grande de play da página: mostra "pausar" quando a lista
            // desta página é a que está tocando.
            document.querySelectorAll('#app-content .play-big').forEach((btn) => {
                const list = document.querySelector(btn.dataset.target || '#app-content [data-tracklist]');
                const mine = !!key && !!list && this.p.listSource === list.dataset.context && !!list.querySelector(`[data-track-key="${key}"]`);
                btn.classList.toggle('is-playing', mine && playing);
                btn.setAttribute('aria-label', mine && playing ? 'Pausar' : 'Tocar');
            });
        }

        // ----- Painel lateral (fila / letra) -----
        openPanel(mode) {
            const panel = $('sidePanel');
            if (this.panelMode === mode) { this.closePanel(); return; }
            this.panelMode = mode;
            panel.hidden = false;
            panel.dataset.mode = mode;
            document.body.classList.add('panel-open');
            $('sidePanelTitle').textContent = mode === 'queue' ? 'Fila' : 'Letra';
            $('queuePanel').hidden = mode !== 'queue';
            $('lyricsPanel').hidden = mode !== 'lyrics';
            $('queueClear').hidden = mode !== 'queue';
            $('btnQueue').classList.toggle('is-active', mode === 'queue');
            $('btnQueue').setAttribute('aria-pressed', String(mode === 'queue'));
            $('btnLyrics').classList.toggle('is-active', mode === 'lyrics');
            $('btnLyrics').setAttribute('aria-pressed', String(mode === 'lyrics'));
            if (mode === 'queue') this.renderQueue();
            if (mode === 'lyrics') this.loadLyrics();
        }

        closePanel() {
            this.panelMode = null;
            $('sidePanel').hidden = true;
            document.body.classList.remove('panel-open');
            ['btnQueue', 'btnLyrics'].forEach((id) => {
                $(id).classList.remove('is-active');
                $(id).setAttribute('aria-pressed', 'false');
            });
        }

        queueItemHtml(t, absIndex, isNow) {
            return `<li class="queue-item${isNow ? ' is-now' : ''}" data-index="${absIndex}">
                ${isNow ? '' : `<button type="button" class="drag-handle" aria-label="Arrastar para reordenar">${RS.icon('drag')}</button>`}
                <img src="${RS.esc(t.cover)}" alt="" loading="lazy">
                <button type="button" class="queue-text" data-queue-jump="${absIndex}">
                    <strong>${RS.esc(t.title)}</strong><small>${RS.esc(t.artist)}</small>
                </button>
                <span class="queue-dur">${t.duration ? RS.fmt(t.duration) : ''}</span>
                ${isNow ? '' : `<button type="button" class="icon-btn" data-queue-remove="${absIndex}" aria-label="Remover da fila">${RS.icon('close')}</button>`}
            </li>`;
        }

        renderQueue() {
            if (this.panelMode !== 'queue') return;
            const p = this.p;
            const now = $('queueNow');
            const list = $('queueList');
            const cur = p.current;
            now.innerHTML = cur ? `<ol class="queue-list">${this.queueItemHtml(cur, p.index, true)}</ol>` : '<p class="side-hint">Nenhuma música tocando.</p>';
            const upcoming = p.queue.slice(p.index + 1, p.index + 201);
            list.innerHTML = upcoming.map((t, i) => this.queueItemHtml(t, p.index + 1 + i, false)).join('');
            $('queueNextTitle').hidden = !upcoming.length;
            list.previousElementSibling.hidden = upcoming.length < 2;
            if (!upcoming.length) list.innerHTML = '<li class="side-hint">A fila está vazia. Use “Adicionar à fila” no menu de uma música.</li>';
        }

        bindQueueSorting() {
            const panel = $('queuePanel');
            panel.addEventListener('click', (e) => {
                const jump = e.target.closest('[data-queue-jump]');
                const remove = e.target.closest('[data-queue-remove]');
                if (remove) this.p.removeAt(Number(remove.dataset.queueRemove));
                else if (jump) this.p.jumpTo(Number(jump.dataset.queueJump));
            });

            // Reordenação com ponteiro (funciona com mouse e toque).
            const list = $('queueList');
            let drag = null;
            list.addEventListener('pointerdown', (e) => {
                const handle = e.target.closest('.drag-handle');
                if (!handle) return;
                const item = handle.closest('.queue-item');
                drag = { item, from: Number(item.dataset.index), pointerId: e.pointerId };
                handle.setPointerCapture(e.pointerId);
                item.classList.add('is-dragging');
                e.preventDefault();
            });
            list.addEventListener('pointermove', (e) => {
                if (!drag) return;
                const items = [...list.querySelectorAll('.queue-item:not(.is-dragging)')];
                const after = items.find((el) => {
                    const r = el.getBoundingClientRect();
                    return e.clientY < r.top + r.height / 2;
                });
                if (after) list.insertBefore(drag.item, after); else list.appendChild(drag.item);
                // Rolagem automática perto das bordas do painel.
                const r = panel.getBoundingClientRect();
                if (e.clientY < r.top + 40) panel.scrollTop -= 12;
                else if (e.clientY > r.bottom - 40) panel.scrollTop += 12;
            });
            const end = () => {
                if (!drag) return;
                const items = [...list.querySelectorAll('.queue-item')];
                const to = this.p.index + 1 + items.indexOf(drag.item);
                drag.item.classList.remove('is-dragging');
                const from = drag.from;
                drag = null;
                if (to !== from) this.p.move(from, to); else this.renderQueue();
            };
            list.addEventListener('pointerup', end);
            list.addEventListener('pointercancel', end);
        }

        // ----- Letras sincronizadas -----
        static parseLyrics(text) {
            const timed = [];
            const plain = [];
            String(text || '').split(/\r?\n/).forEach((raw) => {
                const tags = [...raw.matchAll(/\[(\d{1,3}):(\d{1,2}(?:[.:]\d{1,3})?)\]/g)];
                const content = raw.replace(/\[[^\]]*\]/g, '').trim();
                if (tags.length) {
                    tags.forEach((m) => timed.push({ time: Number(m[1]) * 60 + parseFloat(m[2].replace(':', '.')), text: content }));
                } else if (!/^\s*\[[a-z]+:.*\]\s*$/i.test(raw)) {
                    plain.push({ time: null, text: raw.trim() });
                }
            });
            if (timed.length) return { synced: true, lines: timed.sort((a, b) => a.time - b.time) };
            while (plain.length && !plain[plain.length - 1].text) plain.pop();
            return { synced: false, lines: plain };
        }

        async loadLyrics() {
            const box = $('lyricsLines');
            const t = this.p.current;
            if (!t || t.kind !== 'musica') {
                this.lyrics = { trackKey: null, lines: [], synced: false, active: -1, userScrollUntil: 0 };
                box.innerHTML = '<p class="lyrics-empty">Toque uma música para ver a letra.</p>';
                return;
            }
            const key = `musica:${t.id}`;
            if (this.lyrics.trackKey === key) { this.syncLyrics(true); return; }
            this.lyrics = { trackKey: key, lines: [], synced: false, active: -1, userScrollUntil: 0 };
            box.innerHTML = '<p class="lyrics-empty">Carregando letra…</p>';
            try {
                if (!(key in this.lyricsCache)) {
                    const data = await RS.api(`api/letra.php?id=${t.id}`);
                    this.lyricsCache[key] = data.letra || '';
                }
            } catch (e) {
                this.lyricsCache[key] = '';
            }
            if (this.lyrics.trackKey !== key) return; // trocou de música enquanto carregava
            const parsed = PlayerUI.parseLyrics(this.lyricsCache[key]);
            this.lyrics.lines = parsed.lines;
            this.lyrics.synced = parsed.synced;
            if (!parsed.lines.length) {
                box.innerHTML = `<div class="lyrics-empty">${RS.icon('lyrics', 'empty-icon')}<p>Ainda não temos a letra desta música.</p></div>`;
                return;
            }
            box.classList.toggle('is-synced', parsed.synced);
            box.innerHTML = (parsed.synced ? '' : '<p class="lyrics-note">Letra sem sincronização</p>')
                + parsed.lines.map((l, i) => l.text
                    ? `<p class="lyric-line" data-line="${i}"${parsed.synced ? ` role="button" tabindex="0" data-time="${l.time}"` : ''}>${RS.esc(l.text)}</p>`
                    : `<p class="lyric-line is-gap" data-line="${i}"${parsed.synced ? ` data-time="${l.time}"` : ''}>♪</p>`).join('');
            this.syncLyrics(true);
        }

        syncLyrics(force) {
            const L = this.lyrics;
            if (!L.synced || !L.lines.length) return;
            const time = this.p.audio.currentTime + 0.2;
            let active = -1;
            for (let i = 0; i < L.lines.length; i++) {
                if (L.lines[i].time <= time) active = i; else break;
            }
            if (active === L.active && !force) return;
            const box = $('lyricsLines');
            const prev = box.querySelector('.lyric-line.is-active');
            if (prev) prev.classList.remove('is-active');
            box.querySelectorAll('.lyric-line').forEach((el) => {
                el.classList.toggle('is-past', Number(el.dataset.line) < active);
            });
            L.active = active;
            const el = box.querySelector(`[data-line="${active}"]`);
            if (el) el.classList.add('is-active');
            if (Date.now() < L.userScrollUntil) return;
            const panel = $('lyricsPanel');
            const target = el ? el.offsetTop - panel.clientHeight / 2 + el.clientHeight / 2 : 0;
            panel.scrollTo({ top: target, behavior: force ? 'auto' : 'smooth' });
        }

        bindLyricsScroll() {
            const panel = $('lyricsPanel');
            const pause = () => { this.lyrics.userScrollUntil = Date.now() + 3500; };
            panel.addEventListener('wheel', pause, { passive: true });
            panel.addEventListener('touchmove', pause, { passive: true });
            panel.addEventListener('click', (e) => {
                const line = e.target.closest('.lyric-line[data-time]');
                if (!line) return;
                this.lyrics.userScrollUntil = 0;
                this.p.seek(Number(line.dataset.time));
                if (this.p.audio.paused) this.p.play();
            });
            panel.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter') return;
                const line = e.target.closest('.lyric-line[data-time]');
                if (line) { this.p.seek(Number(line.dataset.time)); e.preventDefault(); }
            });
        }
    }

    RS.PlayerUI = PlayerUI;

    function init() {
        const audio = $('audioPlayer');
        if (!audio) return;
        const player = new Player(audio);
        const ui = new PlayerUI(player);
        RS.player = player;
        RS.playerUI = ui;
        player.bindMediaSession();
        ui.renderModes();
        ui.renderVolume();
        ui.renderSpeed();
        ui.renderTrack(null);
        player.restore();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
