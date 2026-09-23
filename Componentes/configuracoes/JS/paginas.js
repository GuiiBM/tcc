/* =====================================================================
 * paginas.js - Comportamentos específicos de cada página. Cada função em
 * RS.pages[id] roda quando a página com data-page="id" é aberta (tanto no
 * carregamento normal quanto na navegação sem recarregar).
 * ===================================================================== */
(function () {
    'use strict';

    const RS = window.RS;
    const pages = RS.pages = RS.pages || {};

    // ===================== Comum a todas as páginas =====================
    RS.commonInit = function (root) {
        // Abas simples: [data-tabs] com botões [data-tab] e painéis [data-tab-panel].
        root.querySelectorAll('[data-tabs]').forEach((tabs) => {
            tabs.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-tab]');
                if (!btn) return;
                tabs.querySelectorAll('[data-tab]').forEach((b) => {
                    b.classList.toggle('is-active', b === btn);
                    b.setAttribute('aria-selected', String(b === btn));
                });
                root.querySelectorAll('[data-tab-panel]').forEach((p) => { p.hidden = p.dataset.tabPanel !== btn.dataset.tab; });
                const url = new URL(location.href);
                url.searchParams.set('aba', btn.dataset.tab);
                history.replaceState(history.state, '', url);
            });
        });

        // Botão que mostra/esconde um elemento.
        root.querySelectorAll('[data-toggle-target]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const target = root.querySelector(btn.dataset.toggleTarget);
                if (target) target.hidden = !target.hidden;
            });
        });

        // Pré-visualização de imagem escolhida.
        root.querySelectorAll('input[type="file"][data-preview]').forEach((input) => {
            input.addEventListener('change', () => {
                const img = document.querySelector(input.dataset.preview);
                const file = input.files[0];
                if (img && file && img.tagName === 'IMG') img.src = URL.createObjectURL(file);
            });
        });

        root.querySelectorAll('[data-music-form]').forEach(initUploadForm);
        root.querySelectorAll('[data-lrc-tool]').forEach((tool) => initLyricsTool(tool.closest('form')));
        initEpisodeProgress(root);
    };

    // ===================== Formulários com upload de áudio =====================
    // Confere o tamanho dos arquivos antes de enviar (o InfinityFree recusa
    // arquivos acima do limite sem mensagem clara) e mede a duração do áudio.
    function initUploadForm(form) {
        const max = Number(form.dataset.maxBytes) || 0;
        const status = form.querySelector('[data-upload-status]');
        const durationField = form.querySelector('input[name="duracao"]');

        const measure = (src) => new Promise((resolve) => {
            const audio = new Audio();
            audio.preload = 'metadata';
            const done = (value) => { audio.removeAttribute('src'); resolve(value); };
            audio.addEventListener('loadedmetadata', () => done(isFinite(audio.duration) ? Math.round(audio.duration) : 0), { once: true });
            audio.addEventListener('error', () => done(0), { once: true });
            setTimeout(() => done(0), 15000);
            audio.src = src;
        });

        form.querySelectorAll('input[type="file"][data-max-check]').forEach((input) => {
            input.addEventListener('change', async () => {
                const file = input.files[0];
                input.setCustomValidity('');
                if (!file) return;
                if (max && file.size > max) {
                    const mb = (max / 1048576).toFixed(0);
                    input.setCustomValidity(`Arquivo muito grande (${(file.size / 1048576).toFixed(1)} MB). O limite do servidor é ${mb} MB.`);
                    input.reportValidity();
                    return;
                }
                if (input.hasAttribute('data-duration-source') && durationField) {
                    if (status) status.textContent = 'Lendo duração do áudio…';
                    const url = URL.createObjectURL(file);
                    durationField.value = (await measure(url)) || '';
                    URL.revokeObjectURL(url);
                    if (status) status.textContent = durationField.value ? `Duração: ${RS.fmt(durationField.value)}` : '';
                }
            });
        });

        const urlInput = form.querySelector('[data-duration-url]');
        if (urlInput && durationField) {
            urlInput.addEventListener('change', async () => {
                if (!/^https:\/\//i.test(urlInput.value)) return;
                if (status) status.textContent = 'Verificando o link…';
                const seconds = await measure(urlInput.value);
                durationField.value = seconds || '';
                if (status) status.textContent = seconds ? `Duração: ${RS.fmt(seconds)}` : 'Não foi possível ler o áudio desse link (confira se é um link direto para o arquivo).';
            });
        }

        // Admin: mostra só os álbuns do artista escolhido.
        const artistSelect = form.querySelector('[data-artist-select]');
        const albumSelect = form.querySelector('[data-album-select]');
        if (artistSelect && albumSelect) {
            const filter = () => {
                [...albumSelect.options].forEach((opt) => {
                    if (!opt.value) return;
                    opt.hidden = opt.dataset.artist !== artistSelect.value;
                    if (opt.hidden && opt.selected) albumSelect.value = '';
                });
            };
            artistSelect.addEventListener('change', filter);
            filter();
        }

        form.addEventListener('submit', () => {
            if (status && form.querySelector('input[type="file"]') && [...form.querySelectorAll('input[type="file"]')].some((i) => i.files.length)) {
                status.textContent = 'Enviando… isso pode levar alguns instantes.';
            }
        });
        form.addEventListener('rs:saved', () => { if (status) status.textContent = ''; });
    }

    // ===================== Ferramenta de sincronizar letra =====================
    function initLyricsTool(form) {
        const text = form.querySelector('[data-lrc-text]');
        const panel = form.querySelector('[data-lrc-panel]');
        const audio = form.querySelector('[data-lrc-audio]');
        const list = form.querySelector('[data-lrc-lines]');
        const startBtn = form.querySelector('[data-lrc="start"]');
        let lines = [];
        let times = [];
        let active = false;

        const stamp = (t) => {
            const m = Math.floor(t / 60);
            const s = (t % 60).toFixed(2).padStart(5, '0');
            return `[${String(m).padStart(2, '0')}:${s}]`;
        };

        const render = () => {
            list.innerHTML = lines.map((l, i) => `<li class="${i < times.length ? 'is-done' : i === times.length ? 'is-next' : ''}">
                <span class="lrc-time">${i < times.length ? stamp(times[i]).slice(1, -1) : '--:--'}</span>${RS.esc(l)}</li>`).join('');
            const next = list.querySelector('.is-next');
            if (next) next.scrollIntoView({ block: 'center', behavior: 'smooth' });
        };

        const mark = () => {
            if (times.length >= lines.length) return;
            times.push(audio.currentTime);
            render();
            if (times.length === lines.length) RS.toast('Todos os versos marcados. Clique em Concluir.');
        };

        const finish = () => {
            text.value = lines.map((l, i) => (i < times.length ? `${stamp(times[i])} ${l}` : l)).join('\n');
            stop();
            RS.toast('Letra sincronizada. Não esqueça de salvar.', 'success');
        };

        const stop = () => {
            active = false;
            audio.pause();
            panel.hidden = true;
            text.hidden = false;
            startBtn.hidden = false;
        };

        const onKey = (e) => {
            if (!active || e.target.closest('input, textarea')) return;
            if (e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); mark(); }
            if (e.key === 'Backspace') { e.preventDefault(); times.pop(); render(); }
        };
        document.addEventListener('keydown', onKey, true);

        form.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-lrc]');
            if (!btn) return;
            const action = btn.dataset.lrc;
            if (action === 'start') {
                lines = text.value.split(/\r?\n/).map((l) => l.replace(/\[[^\]]*\]/g, '').trim()).filter(Boolean);
                if (!lines.length) { RS.toast('Cole a letra primeiro, um verso por linha.', 'error'); return; }
                times = [];
                active = true;
                if (RS.player) RS.player.pause();
                panel.hidden = false;
                text.hidden = true;
                startBtn.hidden = true;
                audio.currentTime = 0;
                render();
                audio.play().catch(() => {});
            } else if (action === 'mark') {
                mark();
            } else if (action === 'undo') {
                times.pop();
                render();
            } else if (action === 'back') {
                audio.currentTime = Math.max(0, audio.currentTime - 3);
            } else if (action === 'finish') {
                finish();
            } else if (action === 'cancel') {
                stop();
            }
        });
    }

    // ===================== Progresso de episódios =====================
    function initEpisodeProgress(root) {
        const saved = RS.storage.get('ressonance.episodios', {});
        root.querySelectorAll('[data-episode-progress]').forEach((el) => {
            const p = saved[el.dataset.episodeProgress];
            if (!p || !p.d) return;
            const pct = p.done ? 100 : Math.min(100, (p.t / p.d) * 100);
            if (pct < 1) return;
            el.hidden = false;
            el.classList.toggle('is-done', !!p.done);
            el.firstElementChild.style.width = `${pct}%`;
            el.title = p.done ? 'Ouvido' : `Faltam ${RS.fmt(p.d - p.t)}`;
        });
    }

    // ===================== Início =====================
    pages.home = function (root) {
        // Saudação pelo horário do aparelho do usuário.
        const h1 = root.querySelector('[data-greeting]');
        if (h1) {
            const hour = new Date().getHours();
            const greeting = hour >= 5 && hour < 12 ? 'Bom dia' : hour >= 12 && hour < 18 ? 'Boa tarde' : 'Boa noite';
            h1.textContent = h1.dataset.name ? `${greeting}, ${h1.dataset.name}` : greeting;
        }

    };

    // ===================== Busca =====================
    pages.busca = function (root) {
        const input = root.querySelector('#searchInput');
        const results = root.querySelector('#searchResults');
        const browse = root.querySelector('#browseAll');
        const tabs = root.querySelector('#searchTabs');
        const clear = root.querySelector('#searchClear');
        let tipo = 'tudo';
        let timer = null;
        let controller = null;
        let lastKey = '';

        const setTab = (value) => {
            tipo = value;
            tabs.querySelectorAll('[data-search-tab]').forEach((b) => {
                const on = b.dataset.searchTab === value;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-selected', String(on));
            });
        };

        const run = async () => {
            const q = input.value.trim();
            const key = `${q}|${tipo}`;
            if (key === lastKey) return;
            lastKey = key;
            const url = new URL(location.href);
            if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
            history.replaceState(history.state, '', url);
            clear.hidden = !q;
            if (!q) {
                if (controller) controller.abort();
                results.hidden = true;
                tabs.hidden = true;
                browse.hidden = false;
                results.innerHTML = '';
                return;
            }
            browse.hidden = true;
            tabs.hidden = false;
            results.hidden = false;
            results.classList.add('is-loading');
            if (controller) controller.abort();
            controller = new AbortController();
            try {
                const data = await RS.api(`api/buscar.php?q=${encodeURIComponent(q)}&tipo=${tipo}`, undefined, { signal: controller.signal });
                results.innerHTML = data.html;
                RS.initPage(results);
            } catch (err) {
                if (err.name !== 'AbortError') results.innerHTML = `<p class="muted">${RS.esc(err.message)}</p>`;
            } finally {
                results.classList.remove('is-loading');
            }
        };

        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(run, 180);
        });
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); run(); } });
        clear.addEventListener('click', () => { input.value = ''; run(); input.focus(); });
        tabs.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-search-tab]');
            if (!btn) return;
            setTab(btn.dataset.searchTab);
            run();
        });

        if (input.value.trim()) run();
        if (!('ontouchstart' in window)) input.focus({ preventScroll: true });
    };

    // ===================== Biblioteca =====================
    pages.biblioteca = function (root) {
        const chips = root.querySelector('[data-filter-chips]');
        const grid = root.querySelector('[data-filter-grid]');
        if (!chips || !grid) return;
        const apply = (filter) => {
            chips.querySelectorAll('[data-filter]').forEach((c) => c.classList.toggle('is-active', c.dataset.filter === filter));
            grid.querySelectorAll('[data-kind]').forEach((card) => { card.hidden = filter !== 'tudo' && card.dataset.kind !== filter; });
        };
        chips.addEventListener('click', (e) => {
            const chip = e.target.closest('[data-filter]');
            if (chip) apply(chip.dataset.filter);
        });
        const active = chips.querySelector('.is-active');
        apply(active ? active.dataset.filter : 'tudo');
    };

    // ===================== Artista =====================
    pages.artista = function (root) {
        const follow = root.querySelector('[data-follow]');
        if (follow) {
            follow.addEventListener('click', async () => {
                if (!RS.requireLogin('Entre para seguir artistas.')) return;
                follow.disabled = true;
                try {
                    const data = await RS.api('api/seguir.php', { artista_id: Number(follow.dataset.follow) });
                    follow.classList.toggle('is-following', data.seguindo);
                    follow.setAttribute('aria-pressed', String(data.seguindo));
                    follow.textContent = data.seguindo ? 'Seguindo' : 'Seguir';
                    root.querySelectorAll('[data-followers]').forEach((el) => { el.textContent = data.seguidores.toLocaleString('pt-BR'); });
                    RS.toast(data.seguindo ? 'Agora você segue este artista' : 'Você deixou de seguir');
                } catch (err) {
                    RS.toast(err.message, 'error');
                } finally {
                    follow.disabled = false;
                }
            });
        }

        const disco = root.querySelector('[data-disco-tabs]');
        if (disco) {
            disco.addEventListener('click', (e) => {
                const chip = e.target.closest('[data-disco]');
                if (!chip) return;
                disco.querySelectorAll('[data-disco]').forEach((c) => c.classList.toggle('is-active', c === chip));
                root.querySelectorAll('[data-disco-panel]').forEach((p) => { p.hidden = p.dataset.discoPanel !== chip.dataset.disco; });
            });
        }
    };

    // ===================== Perfil =====================
    pages.perfil = function (root) {
        const quality = root.querySelector('[data-quality-form]');
        if (quality) {
            quality.addEventListener('rs:saved', (e) => {
                if (window.APP.usuario) window.APP.usuario.qualidade = e.detail.qualidade;
            });
        }
        if (location.hash) {
            const target = root.querySelector(location.hash);
            if (target) setTimeout(() => target.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
        }
    };
})();
