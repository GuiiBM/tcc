/* =====================================================================
 * app.js - Interface geral do Ressonance: navegação sem recarregar a
 * página (o player não para), menus de contexto, modais, avisos,
 * curtidas, playlists, carrosséis e atalhos de teclado.
 * ===================================================================== */
(function () {
    'use strict';

    const RS = window.RS;
    const APP = window.APP = window.APP || { logado: false, curtidas: [], playlists: [] };
    const $ = (id) => document.getElementById(id);
    const isMobile = () => window.matchMedia('(max-width: 768px)').matches;

    // ===================== Avisos (toasts) =====================
    RS.toast = function (message, type = 'info', action) {
        const box = $('toasts');
        if (!box) return;
        const el = document.createElement('div');
        el.className = `rs-toast rs-toast-${type}`;
        el.innerHTML = `<span>${RS.esc(message)}</span>`;
        if (action) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'rs-toast-action';
            btn.textContent = action.label;
            btn.addEventListener('click', () => { action.onClick(); el.remove(); });
            el.appendChild(btn);
        }
        box.appendChild(el);
        requestAnimationFrame(() => el.classList.add('is-visible'));
        setTimeout(() => {
            el.classList.remove('is-visible');
            setTimeout(() => el.remove(), 300);
        }, type === 'error' ? 5000 : 3200);
    };

    // ===================== Modais =====================
    let openModalCount = 0;
    RS.modalOpen = () => openModalCount > 0;

    // Abre um modal. body pode ser HTML; retorna uma Promise com o valor do
    // botão clicado (ou null se fechado). Se houver <form> no corpo, o
    // submit resolve com o FormData (quando onSubmit não é informado).
    RS.modal = function ({ title, body = '', actions = [], wide = false, onOpen, onSubmit }) {
        return new Promise((resolve) => {
            const root = $('modalRoot');
            const lastFocus = document.activeElement;
            const wrap = document.createElement('div');
            wrap.className = 'rs-modal-backdrop';
            wrap.innerHTML = `<div class="modal-box${wide ? ' is-wide' : ''}" role="dialog" aria-modal="true" aria-labelledby="modalTitle${openModalCount}">
                <div class="modal-head"><h2 id="modalTitle${openModalCount}">${RS.esc(title)}</h2>
                <button type="button" class="icon-btn" data-modal-close aria-label="Fechar">${RS.icon('close')}</button></div>
                <div class="rs-modal-body">${body}</div>
                ${actions.length ? `<div class="modal-actions">${actions.map((a, i) => `<button type="${a.submit ? 'submit' : 'button'}" class="btn-pill ${a.primary ? 'btn-accent' : a.danger ? 'btn-danger' : 'btn-ghost'}" data-modal-action="${i}"${a.submit ? ' form="modalForm"' : ''}>${RS.esc(a.label)}</button>`).join('')}</div>` : ''}
            </div>`;
            root.appendChild(wrap);
            openModalCount++;
            requestAnimationFrame(() => wrap.classList.add('is-visible'));

            let done = false;
            const close = (value) => {
                if (done) return;
                done = true;
                openModalCount--;
                document.removeEventListener('keydown', onKey, true);
                wrap.classList.remove('is-visible');
                setTimeout(() => wrap.remove(), 180);
                if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
                resolve(value);
            };
            const onKey = (e) => {
                if (e.key === 'Escape') { e.stopPropagation(); close(null); }
                if (e.key === 'Tab') {
                    const focusables = [...wrap.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')].filter((el) => !el.disabled && el.offsetParent !== null);
                    if (!focusables.length) return;
                    const first = focusables[0];
                    const last = focusables[focusables.length - 1];
                    if (e.shiftKey && document.activeElement === first) { last.focus(); e.preventDefault(); } else if (!e.shiftKey && document.activeElement === last) { first.focus(); e.preventDefault(); }
                }
            };
            document.addEventListener('keydown', onKey, true);
            wrap.addEventListener('mousedown', (e) => { if (e.target === wrap) close(null); });
            wrap.querySelector('[data-modal-close]').addEventListener('click', () => close(null));
            wrap.querySelectorAll('[data-modal-action]').forEach((btn) => {
                const action = actions[Number(btn.dataset.modalAction)];
                if (action.submit) return;
                btn.addEventListener('click', () => close(action.value !== undefined ? action.value : true));
            });
            const form = wrap.querySelector('form');
            if (form) {
                form.id = 'modalForm';
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    if (!onSubmit) { close(new FormData(form)); return; }
                    const buttons = wrap.querySelectorAll('.modal-actions button');
                    buttons.forEach((b) => { b.disabled = true; });
                    try {
                        const result = await onSubmit(new FormData(form), form);
                        if (result !== false) close(result === undefined ? true : result);
                    } catch (err) {
                        RS.toast(err.message, 'error');
                    } finally {
                        buttons.forEach((b) => { b.disabled = false; });
                    }
                });
            }
            if (onOpen) onOpen(wrap);
            const autofocus = wrap.querySelector('[autofocus]') || wrap.querySelector('input, textarea, select, .modal-actions button');
            if (autofocus) setTimeout(() => autofocus.focus(), 30);
        });
    };

    RS.confirm = function (title, text, confirmLabel = 'Confirmar', danger = true) {
        return RS.modal({
            title,
            body: `<p class="modal-text">${RS.esc(text)}</p>`,
            actions: [{ label: 'Cancelar', value: false }, { label: confirmLabel, value: true, danger, primary: !danger }],
        });
    };

    RS.requireLogin = function (message) {
        if (APP.logado) return true;
        RS.modal({
            title: 'Entre na sua conta',
            body: `<p class="modal-text">${RS.esc(message || 'Entre com sua conta para usar este recurso.')}</p>`,
            actions: [{ label: 'Agora não', value: false }, { label: 'Entrar', value: true, primary: true }],
        }).then((ok) => { if (ok) window.location.href = 'login.php'; });
        return false;
    };

    // ===================== Curtidas =====================
    const liked = new Set((APP.curtidas || []).map(Number));
    RS.isLiked = (id) => liked.has(Number(id));

    RS.syncLikes = function (root = document) {
        root.querySelectorAll('.like-toggle[data-like-id]').forEach((btn) => {
            const id = Number(btn.dataset.likeId);
            const on = !!id && liked.has(id);
            btn.classList.toggle('is-liked', on);
            btn.setAttribute('aria-pressed', String(on));
            const label = on ? 'Remover de Músicas Curtidas' : 'Salvar em Músicas Curtidas';
            btn.setAttribute('aria-label', label);
            btn.title = btn.id === 'playerLike' ? `${label} (C)` : label;
        });
    };

    RS.toggleLike = async function (id) {
        id = Number(id);
        if (!id || !RS.requireLogin('Entre para salvar músicas em Músicas Curtidas.')) return;
        const wasLiked = liked.has(id);
        if (wasLiked) liked.delete(id); else liked.add(id);
        RS.syncLikes();
        try {
            await RS.api('api/curtir.php', { musica_id: id, acao: wasLiked ? 'remover' : 'curtir' });
            RS.toast(wasLiked ? 'Removida de Músicas Curtidas' : 'Adicionada a Músicas Curtidas');
            if (RS.player && RS.player.current && RS.player.current.id === id) RS.loadReaction(id);
            document.dispatchEvent(new CustomEvent('rs:likes', { detail: { id, liked: !wasLiked } }));
        } catch (err) {
            if (wasLiked) liked.add(id); else liked.delete(id);
            RS.syncLikes();
            RS.toast(err.message, 'error');
        }
    };

    // Estado de "não gostei" + contadores da música atual no player.
    RS.loadReaction = async function (id) {
        const dislike = $('playerDislike');
        const count = $('dislikeCount');
        try {
            const data = await RS.api(`api/curtir.php?musica_id=${id}`);
            if (!RS.player.current || RS.player.current.id !== id) return;
            dislike.classList.toggle('is-active', !!data.descurtida);
            dislike.setAttribute('aria-pressed', String(!!data.descurtida));
            count.textContent = data.descurtidas > 0 ? data.descurtidas : '';
            if (data.curtida) liked.add(id); else liked.delete(id);
            RS.syncLikes();
        } catch (e) { /* sem conexão: mantém o estado atual */ }
    };

    async function toggleDislike() {
        const t = RS.player.current;
        if (!t || t.kind !== 'musica' || !RS.requireLogin('Entre para avaliar músicas.')) return;
        const on = $('playerDislike').classList.contains('is-active');
        try {
            await RS.api('api/curtir.php', { musica_id: t.id, acao: on ? 'remover' : 'descurtir' });
            if (!on) liked.delete(t.id);
            RS.loadReaction(t.id);
        } catch (err) {
            RS.toast(err.message, 'error');
        }
    }

    // ===================== Tocar faixas das listas =====================
    function trackOf(el) {
        try { return JSON.parse(el.dataset.track); } catch (e) { return null; }
    }

    // Grupo de faixas "irmãs" de um elemento (mesma lista / mesma prateleira).
    function siblingsOf(el) {
        const group = el.closest('[data-tracklist], .shelf-track, .card-grid, [data-track-group]');
        const nodes = group ? [...group.querySelectorAll('[data-track]')] : [el];
        return { nodes, tracks: nodes.map(trackOf).filter(Boolean) };
    }

    function isCurrent(el) {
        const t = RS.player && RS.player.current;
        return !!t && el.dataset.trackKey === `${t.kind}:${t.id}`;
    }

    function playFromElement(el) {
        if (isCurrent(el)) { RS.player.toggle(); return; }
        const { nodes, tracks } = siblingsOf(el);
        const index = nodes.indexOf(el);
        RS.player.playList(tracks, Math.max(0, index));
        const list = el.closest('[data-tracklist]');
        RS.player.listSource = list ? list.dataset.context : null;
        RS.player.contextUrl = null;
    }

    RS.playUrl = async function (url, { shuffle = false, button } = {}) {
        if (RS.player.contextUrl === url && RS.player.current) { RS.player.toggle(); return; }
        if (button) button.classList.add('is-loading');
        try {
            const data = await RS.api(url);
            if (!data.faixas || !data.faixas.length) { RS.toast('Nada para tocar aqui ainda.'); return; }
            if (shuffle) RS.player.setShuffle(true);
            RS.player.playList(data.faixas, shuffle ? Math.floor(Math.random() * data.faixas.length) : 0);
            RS.player.contextUrl = url;
            RS.player.listSource = null;
        } catch (err) {
            RS.toast(err.message, 'error');
        } finally {
            if (button) button.classList.remove('is-loading');
        }
    };

    // Botão grande de play de uma página (álbum, playlist...): toca a
    // lista de faixas indicada, ou alterna pausa se ela já estiver tocando.
    function playAll(btn, shuffle) {
        const list = document.querySelector(btn.dataset.target || '#app-content [data-tracklist]');
        if (!list) {
            if (btn.dataset.url) RS.playUrl(btn.dataset.url, { shuffle, button: btn });
            return;
        }
        const nodes = [...list.querySelectorAll('[data-track]')];
        const tracks = nodes.map(trackOf).filter(Boolean);
        if (!tracks.length) { RS.toast('Nada para tocar aqui ainda.'); return; }
        const current = RS.player.current;
        const playingThisList = current && nodes.some((n) => n.dataset.trackKey === `${current.kind}:${current.id}`) && RS.player.listSource === list.dataset.context;
        if (playingThisList && !shuffle) { RS.player.toggle(); return; }
        if (shuffle) RS.player.setShuffle(true);
        RS.player.playList(tracks, shuffle ? Math.floor(Math.random() * tracks.length) : 0);
        RS.player.listSource = list.dataset.context;
        RS.player.contextUrl = null;
        RS.playerUI.markPlayingRows();
    }

    // ===================== Menu de contexto das faixas =====================
    const menu = () => $('ctxMenu');
    let menuTrack = null;
    let menuSource = null;
    let menuOpenedAt = 0;

    function closeMenu() {
        const m = menu();
        if (!m || m.hidden) return;
        m.hidden = true;
        m.classList.remove('is-sheet');
        document.body.classList.remove('menu-open');
        if (menuSource && menuSource.focus) menuSource.focus({ preventScroll: true });
        menuSource = null;
    }
    RS.closeMenu = closeMenu;

    function menuItem(action, icon, label, extra = '') {
        return `<button type="button" role="menuitem" class="ctx-item" data-menu="${action}" ${extra}>${RS.icon(icon)}<span>${RS.esc(label)}</span></button>`;
    }

    function openTrackMenu(el, anchor, point) {
        const track = trackOf(el);
        if (!track) return;
        menuTrack = track;
        menuSource = anchor;
        const m = menu();
        const isSong = track.kind === 'musica';
        const list = el.closest('[data-playlist-id]');
        const canRemove = list && list.dataset.owner === '1';
        let html = `<div class="ctx-title"><img src="${RS.esc(track.cover)}" alt=""><div><strong>${RS.esc(track.title)}</strong><small>${RS.esc(track.artist)}</small></div></div>`;
        html += menuItem('play-next', 'play-next', 'Tocar a seguir');
        html += menuItem('queue', 'queue-add', 'Adicionar à fila');
        if (isSong) {
            html += menuItem('playlist', 'plus', 'Adicionar à playlist', 'aria-haspopup="true"');
            html += `<div class="ctx-sub" id="ctxPlaylists" hidden></div>`;
            html += menuItem('like', RS.isLiked(track.id) ? 'heart-fill' : 'heart', RS.isLiked(track.id) ? 'Remover de Músicas Curtidas' : 'Salvar em Músicas Curtidas');
        }
        if (canRemove) html += menuItem('remove-from-playlist', 'trash', 'Remover desta playlist', `data-playlist="${list.dataset.playlistId}"`);
        html += '<div class="ctx-sep"></div>';
        if (track.artistId) html += menuItem('artist', 'user', 'Ir para o artista');
        if (track.albumId) html += menuItem('album', 'album', 'Ir para o álbum');
        if (track.podcastId) html += menuItem('podcast', 'podcast', 'Ir para o podcast');
        m.innerHTML = html;
        m.hidden = false;
        menuOpenedAt = Date.now();
        document.body.classList.add('menu-open');

        if (isMobile()) {
            m.classList.add('is-sheet');
            m.style.left = '';
            m.style.top = '';
        } else {
            m.classList.remove('is-sheet');
            const rect = anchor.getBoundingClientRect();
            const x = point ? point.x : rect.right;
            const y = point ? point.y : rect.bottom;
            const mw = m.offsetWidth;
            const mh = m.offsetHeight;
            m.style.left = `${Math.max(8, Math.min(x - (point ? 0 : mw), window.innerWidth - mw - 8))}px`;
            m.style.top = `${Math.max(8, y + mh > window.innerHeight - 8 ? y - mh - (point ? 0 : rect.height) : y)}px`;
        }
        const first = m.querySelector('.ctx-item');
        if (first) first.focus({ preventScroll: true });
    }

    function renderPlaylistSubmenu() {
        const sub = $('ctxPlaylists');
        if (!sub) return;
        if (!sub.hidden) { sub.hidden = true; return; }
        const items = (APP.playlists || []).map((p) => `<button type="button" role="menuitem" class="ctx-item" data-menu="add-to" data-playlist="${p.id}">${RS.icon('music')}<span>${RS.esc(p.nome)}</span></button>`).join('');
        sub.innerHTML = `<button type="button" role="menuitem" class="ctx-item" data-menu="new-playlist">${RS.icon('plus')}<span>Nova playlist</span></button>${items}`;
        sub.hidden = false;
    }

    async function onMenuAction(btn) {
        const action = btn.dataset.menu;
        const t = menuTrack;
        if (!t) return;
        if (action === 'playlist') {
            if (!RS.requireLogin('Entre para criar playlists e adicionar músicas.')) { closeMenu(); return; }
            renderPlaylistSubmenu();
            return;
        }
        closeMenu();
        switch (action) {
            case 'play-next': RS.player.playNext(t); RS.toast('Vai tocar a seguir'); break;
            case 'queue': RS.player.addToQueue(t); RS.toast('Adicionada à fila'); break;
            case 'like': RS.toggleLike(t.id); break;
            case 'artist': RS.navigate(`artista.php?id=${t.artistId}`); break;
            case 'album': RS.navigate(`album.php?id=${t.albumId}`); break;
            case 'podcast': RS.navigate(`podcast.php?id=${t.podcastId}`); break;
            case 'add-to': RS.addToPlaylist(Number(btn.dataset.playlist), t.id); break;
            case 'new-playlist': RS.createPlaylist({ musicaId: t.id }); break;
            case 'remove-from-playlist': RS.removeFromPlaylist(Number(btn.dataset.playlist), t.id); break;
            default: break;
        }
    }

    // ===================== Playlists =====================
    function playlistForm(p = {}) {
        return `<form class="form-stack" enctype="multipart/form-data">
            <div class="playlist-form">
                <label class="cover-picker" title="Escolher capa">
                    <img src="${RS.esc(p.capa || 'Componentes/icones/icone.png')}" alt="" id="coverPreview">
                    <span>${RS.icon('edit')} Escolher capa</span>
                    <input type="file" name="capa" accept="image/*" hidden>
                </label>
                <div class="form-stack">
                    <label class="field"><span>Nome</span><input type="text" name="nome" maxlength="100" required value="${RS.esc(p.nome || '')}" placeholder="Minha playlist" autofocus></label>
                    <label class="field"><span>Descrição</span><textarea name="descricao" rows="3" maxlength="300" placeholder="Adicione uma descrição (opcional)">${RS.esc(p.descricao || '')}</textarea></label>
                </div>
            </div>
            <label class="switch"><input type="checkbox" name="publica" value="1" ${p.publica === false ? '' : 'checked'}><span class="switch-ui"></span><span>Playlist pública (aparece na busca)</span></label>
            ${p.capa ? '<label class="check"><input type="checkbox" name="remover_capa" value="1"> Remover capa atual</label>' : ''}
        </form>`;
    }

    function bindCoverPreview(wrap) {
        const input = wrap.querySelector('input[name="capa"]');
        const img = wrap.querySelector('#coverPreview');
        if (!input || !img) return;
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (file) img.src = URL.createObjectURL(file);
        });
    }

    RS.refreshPlaylists = async function () {
        try {
            const data = await RS.api('api/playlists.php?acao=listar');
            APP.playlists = data.playlists.map((p) => ({ id: p.id, nome: p.nome }));
            const box = $('sidebarPlaylists');
            if (box) {
                box.innerHTML = data.playlists.map((p) => `<a class="library-item" data-nav="playlist.php?id=${p.id}" href="playlist.php?id=${p.id}">
                    <span class="library-thumb">${p.capa ? `<img src="${RS.esc(p.capa)}" alt="" loading="lazy">` : RS.icon('music')}</span>
                    <span class="library-text"><strong>${RS.esc(p.nome)}</strong><small>Playlist • ${p.total} ${p.total === 1 ? 'música' : 'músicas'}</small></span></a>`).join('');
                markActiveNav();
                fitSidebar();
            }
        } catch (e) { /* mantém a lista atual */ }
    };

    RS.createPlaylist = function ({ musicaId } = {}) {
        if (!RS.requireLogin('Entre para criar playlists.')) return;
        RS.modal({
            title: 'Criar playlist',
            body: playlistForm({ nome: `Minha playlist nº ${(APP.playlists || []).length + 1}` }),
            actions: [{ label: 'Cancelar', value: null }, { label: 'Criar', primary: true, submit: true }],
            onOpen: bindCoverPreview,
            onSubmit: async (fd) => {
                fd.append('acao', 'criar');
                const data = await RS.api('api/playlists.php', fd);
                if (musicaId) {
                    await RS.api('api/playlists.php', { acao: 'adicionar', playlist_id: data.id, musica_id: musicaId });
                    RS.toast(`Adicionada a ${data.nome}`);
                } else {
                    RS.toast('Playlist criada');
                    RS.navigate(`playlist.php?id=${data.id}`);
                }
                RS.refreshPlaylists();
            },
        });
    };

    RS.editPlaylist = function (btn) {
        const p = JSON.parse(btn.dataset.playlist);
        RS.modal({
            title: 'Editar detalhes',
            body: playlistForm(p),
            actions: [{ label: 'Cancelar', value: null }, { label: 'Salvar', primary: true, submit: true }],
            onOpen: bindCoverPreview,
            onSubmit: async (fd) => {
                fd.append('acao', 'editar');
                fd.append('playlist_id', p.id);
                await RS.api('api/playlists.php', fd);
                RS.toast('Playlist atualizada');
                RS.refreshPlaylists();
                RS.reload();
            },
        });
    };

    RS.deletePlaylist = async function (id, nome) {
        const ok = await RS.confirm('Excluir playlist?', `A playlist “${nome}” será excluída para sempre.`, 'Excluir');
        if (!ok) return;
        try {
            await RS.api('api/playlists.php', { acao: 'excluir', playlist_id: id });
            RS.toast('Playlist excluída');
            RS.refreshPlaylists();
            RS.navigate('biblioteca.php', { replace: true });
        } catch (err) {
            RS.toast(err.message, 'error');
        }
    };

    RS.addToPlaylist = async function (playlistId, musicaId) {
        try {
            const data = await RS.api('api/playlists.php', { acao: 'adicionar', playlist_id: playlistId, musica_id: musicaId });
            RS.toast(data.message);
            RS.refreshPlaylists();
            const open = document.querySelector(`#app-content[data-page="playlist"] [data-playlist-id="${playlistId}"]`);
            if (open) RS.reload();
        } catch (err) {
            RS.toast(err.message, 'error');
        }
    };

    RS.removeFromPlaylist = async function (playlistId, musicaId) {
        try {
            await RS.api('api/playlists.php', { acao: 'remover', playlist_id: playlistId, musica_id: musicaId });
            RS.toast('Removida da playlist');
            RS.refreshPlaylists();
            if (document.querySelector(`#app-content [data-playlist-id="${playlistId}"]`)) RS.reload();
        } catch (err) {
            RS.toast(err.message, 'error');
        }
    };

    // ===================== Navegação sem recarregar =====================
    const SPA_PAGES = new Set(['', 'index.php', 'buscar.php', 'genero.php', 'album.php', 'playlist.php', 'curtidas.php', 'biblioteca.php', 'historico.php', 'artista.php', 'artistas.php', 'recomendados.php', 'podcasts.php', 'podcast.php', 'perfil.php', 'privacidade.php']);
    const basePath = new URL(document.baseURI).pathname;
    let navController = null;
    let currentUrl = location.href;

    function isSpaUrl(url) {
        if (url.origin !== location.origin) return false;
        if (!url.pathname.startsWith(basePath)) return false;
        const rest = decodeURIComponent(url.pathname.slice(basePath.length));
        if (rest.includes('/')) return false;
        return SPA_PAGES.has(rest);
    }

    function markActiveNav() {
        const url = new URL(location.href);
        const file = url.pathname.slice(basePath.length) || 'index.php';
        const withQuery = file + url.search;
        const groups = {
            'buscar.php': ['genero.php', 'podcasts.php', 'podcast.php'], 'artistas.php': ['artista.php'],
            'biblioteca.php': ['curtidas.php', 'historico.php', 'playlist.php'],
        };
        document.querySelectorAll('[data-nav]').forEach((a) => {
            const nav = a.dataset.nav;
            const isBottom = a.classList.contains('bottom-item');
            let active = nav === withQuery || (!nav.includes('?') && nav === file);
            if (!active && groups[nav]) active = groups[nav].includes(file) && (isBottom || nav !== 'biblioteca.php');
            a.classList.toggle('is-active', active);
            if (active) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
        });
    }

    function runScripts(container) {
        container.querySelectorAll('script').forEach((old) => {
            const script = document.createElement('script');
            [...old.attributes].forEach((attr) => script.setAttribute(attr.name, attr.value));
            script.textContent = old.textContent;
            old.replaceWith(script);
        });
    }

    async function navigate(href, { push = true, replace = false, restoreScroll = null } = {}) {
        const url = new URL(href, document.baseURI);
        if (!isSpaUrl(url)) { window.location.href = url.href; return; }
        closeMenu();
        closeUserMenu();
        if (navController) navController.abort();
        navController = new AbortController();
        const main = $('appMain');
        if (push || replace) {
            history.replaceState(Object.assign({}, history.state, { scroll: main.scrollTop }), '');
        }
        const loading = $('pageLoading');
        loading.hidden = false;
        loading.classList.remove('is-done');

        try {
            const response = await fetch(url.href, { headers: { 'X-SPA': '1' }, credentials: 'same-origin', signal: navController.signal });
            const finalUrl = new URL(response.url);
            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const next = doc.getElementById('app-content');
            if (!next || !next.dataset.spa || !isSpaUrl(finalUrl)) {
                window.location.href = finalUrl.href;
                return;
            }
            const current = $('app-content');
            const imported = document.importNode(next, true);
            current.replaceWith(imported);
            document.title = doc.title;
            const target = finalUrl.href + (url.hash || '');
            if (replace) history.replaceState({ scroll: 0 }, '', target);
            else if (push) history.pushState({ scroll: 0 }, '', target);
            currentUrl = location.href;
            runScripts(imported);
            markActiveNav();
            main.scrollTop = restoreScroll !== null ? restoreScroll : 0;
            RS.initPage(imported);
            if (push) imported.focus({ preventScroll: true });
        } catch (err) {
            if (err.name === 'AbortError') return;
            window.location.href = url.href;
        } finally {
            loading.classList.add('is-done');
            setTimeout(() => { loading.hidden = true; }, 250);
        }
    }

    RS.navigate = navigate;
    RS.reload = () => navigate(location.href, { push: false, restoreScroll: $('appMain').scrollTop });

    window.addEventListener('popstate', (e) => {
        const player = $('player');
        if (player && player.classList.contains('is-expanded') && location.href === currentUrl) {
            RS.playerUI.expand(false);
            return;
        }
        if (location.href === currentUrl) return;
        currentUrl = location.href;
        navigate(location.href, { push: false, restoreScroll: e.state && typeof e.state.scroll === 'number' ? e.state.scroll : 0 });
    });

    // ===================== Carrosséis (prateleiras) =====================
    function updateShelf(shelf) {
        const track = shelf.querySelector('.shelf-track');
        if (!track) return;
        const [prev, next] = shelf.querySelectorAll('.shelf-nav');
        if (!prev || !next) return;
        prev.disabled = track.scrollLeft <= 4;
        next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
        shelf.classList.toggle('is-scrollable', track.scrollWidth > track.clientWidth + 4);
    }

    function initShelves(root) {
        root.querySelectorAll('.shelf').forEach((shelf) => {
            const track = shelf.querySelector('.shelf-track');
            if (!track || track.dataset.bound) return;
            track.dataset.bound = '1';
            track.addEventListener('scroll', () => updateShelf(shelf), { passive: true });
            updateShelf(shelf);
        });
    }
    window.addEventListener('resize', () => document.querySelectorAll('.shelf').forEach(updateShelf));

    // ===================== Formulários via API =====================
    // <form data-api-form action="api/...">: envia com fetch, mostra o
    // aviso e opcionalmente recarrega (data-reload) ou navega (data-redirect).
    async function submitApiForm(form, submitter) {
        if (form.dataset.confirm && !(await RS.confirm('Tem certeza?', form.dataset.confirm, 'Confirmar'))) return;
        const fd = new FormData(form);
        if (submitter && submitter.name) fd.append(submitter.name, submitter.value);
        const buttons = form.querySelectorAll('button[type="submit"]');
        buttons.forEach((b) => { b.disabled = true; });
        try {
            const data = await RS.api(form.getAttribute('action'), fd);
            if (data.message) RS.toast(data.message, 'success');
            if (form.dataset.reset !== undefined) form.reset();
            if (form.dataset.fullReload !== undefined) { window.location.reload(); return; }
            if (data.redirect || form.dataset.redirect) RS.navigate(data.redirect || form.dataset.redirect);
            else if (form.dataset.reload !== undefined) RS.reload();
            form.dispatchEvent(new CustomEvent('rs:saved', { detail: data }));
        } catch (err) {
            RS.toast(err.message, 'error');
        } finally {
            buttons.forEach((b) => { b.disabled = false; });
        }
    }

    // ===================== Localização (recomendações locais) =====================
    // Pede a posição ao navegador e guarda só no cookie rs_local, arredondada
    // a 2 casas (~1 km). O servidor usa para ordenar artistas por distância.
    function useLocation(btn) {
        if (!navigator.geolocation) { RS.toast('Seu navegador não informa a localização.', 'error'); return; }
        btn.disabled = true;
        navigator.geolocation.getCurrentPosition((pos) => {
            const lat = pos.coords.latitude.toFixed(2);
            const lon = pos.coords.longitude.toFixed(2);
            document.cookie = `rs_local=${lat},${lon}; Max-Age=${30 * 24 * 3600}; path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
            RS.toast('Pronto! Mostrando artistas perto de você.', 'success');
            RS.reload();
        }, (err) => {
            btn.disabled = false;
            RS.toast(err.code === 1 ? 'Permissão de localização negada.' : 'Não foi possível obter sua localização.', 'error');
        }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 3600000 });
    }

    // ===================== Propagandas =====================
    // Carrossel das propagandas (barra lateral e início no celular): troca a
    // cada 7 s, pausa com o mouse em cima e amplia ao clicar.
    function initAds(root) {
        root.querySelectorAll('[data-ad-slider]').forEach((slider) => {
            if (slider.dataset.bound) return;
            slider.dataset.bound = '1';
            const slides = [...slider.querySelectorAll('.ad-slide')];
            const dots = [...slider.querySelectorAll('.ad-dot')];
            let current = 0;
            let timer = null;
            const go = (i) => {
                current = (i + slides.length) % slides.length;
                slides.forEach((s, j) => { s.classList.toggle('is-active', j === current); s.tabIndex = j === current ? 0 : -1; });
                dots.forEach((d, j) => d.classList.toggle('is-active', j === current));
            };
            const restart = () => {
                clearInterval(timer);
                if (slides.length < 2) return;
                timer = setInterval(() => {
                    if (!document.body.contains(slider)) { clearInterval(timer); return; }
                    if (!document.hidden) go(current + 1);
                }, 7000);
            };
            slider.addEventListener('click', (e) => {
                const dot = e.target.closest('[data-ad-go]');
                const step = e.target.closest('[data-ad-step]');
                const slide = e.target.closest('[data-ad-image]');
                if (dot) { go(Number(dot.dataset.adGo)); restart(); } else if (step) { go(current + Number(step.dataset.adStep)); restart(); } else if (slide) {
                    RS.modal({ title: 'Publicidade', body: `<img class="ad-full" src="${RS.esc(slide.dataset.adImage)}" alt="Anúncio">` });
                }
            });
            slider.addEventListener('mouseenter', () => clearInterval(timer));
            slider.addEventListener('mouseleave', restart);
            restart();
        });
    }
    RS.initAds = initAds;

    // Mostra o anúncio da barra lateral só se ainda sobrar espaço para a
    // lista de playlists (ao menos ~2 itens); senão ele vai para o início.
    function fitSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const library = sidebar && sidebar.querySelector('.sidebar-library');
        if (!library || !sidebar.querySelector('.sidebar-ad')) return;
        sidebar.classList.remove('no-ad', 'cta-compacto');
        const head = library.querySelector('.library-head');
        const list = library.querySelector('.library-list');
        const cta = library.querySelector('.library-cta');
        const cabe = () => {
            const conteudo = list ? Math.min(list.scrollHeight, 130) : (cta ? cta.offsetHeight : 0);
            return library.clientHeight >= (head ? head.offsetHeight : 0) + conteudo + 12;
        };
        if (cabe()) return;
        // Visitante: tenta a versão curta do convite para criar playlist.
        if (cta) {
            sidebar.classList.add('cta-compacto');
            if (cabe()) return;
            sidebar.classList.remove('cta-compacto');
        }
        sidebar.classList.add('no-ad');
    }
    RS.fitSidebar = fitSidebar;
    let fitTimer = null;
    window.addEventListener('resize', () => { clearTimeout(fitTimer); fitTimer = setTimeout(fitSidebar, 120); });

    // ===================== Menu do usuário =====================
    function closeUserMenu() {
        const m = $('userMenu');
        if (!m || m.hidden) return;
        m.hidden = true;
        const btn = document.querySelector('[data-action="user-menu"]');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    }

    // ===================== Atalhos de teclado =====================
    const SHORTCUTS = [
        ['Espaço', 'Tocar / pausar'],
        ['← / →', 'Voltar / avançar 5 s (15 s em podcasts)'],
        ['Shift + ← / →', 'Faixa anterior / próxima'],
        ['Shift + ↑ / ↓', 'Aumentar / diminuir volume'],
        ['M', 'Silenciar / ativar som'],
        ['S', 'Modo aleatório'],
        ['R', 'Repetir (desligado → fila → música)'],
        ['C', 'Curtir a música atual'],
        ['Q', 'Abrir / fechar a fila'],
        ['Y', 'Abrir / fechar a letra'],
        ['0 – 9', 'Pular para 0% – 90% da faixa'],
        ['Shift + > / <', 'Velocidade do podcast'],
        ['/', 'Buscar'],
        ['X', 'Fechar o player (tirar a música)'],
        ['Esc', 'Fechar painel, menu ou player'],
        ['?', 'Mostrar esta lista'],
    ];

    function showShortcuts() {
        RS.modal({
            title: 'Atalhos do teclado',
            body: `<dl class="shortcuts">${SHORTCUTS.map(([k, d]) => `<div><dt>${k.split(' ').map((p) => (['+', '/', '–'].includes(p) ? ` ${p} ` : `<kbd>${RS.esc(p)}</kbd>`)).join('')}</dt><dd>${RS.esc(d)}</dd></div>`).join('')}</dl>`,
            actions: [{ label: 'Fechar', primary: true }],
        });
    }

    function onKeyDown(e) {
        if (e.defaultPrevented || e.isComposing) return;
        const t = e.target;
        const typing = t.closest && (t.closest('input, textarea, select, [contenteditable="true"]'));
        if (e.key === 'Escape') {
            if (!$('ctxMenu').hidden) { closeMenu(); return; }
            if ($('userMenu') && !$('userMenu').hidden) { closeUserMenu(); return; }
            if (typing) { t.blur(); return; }
            if ($('player').classList.contains('is-expanded')) { history.back(); return; }
            if (RS.playerUI && RS.playerUI.panelMode) { RS.playerUI.closePanel(); return; }
            return;
        }
        if (typing || RS.modalOpen() || e.ctrlKey || e.metaKey || e.altKey) return;
        const p = RS.player;
        const onControl = t.closest && t.closest('button, a, [role="button"], [role="slider"], summary');
        const key = e.key;
        let handled = true;

        if (key === ' ' && !onControl) {
            p.toggle();
        } else if (key === 'ArrowRight' && !t.closest('[role="slider"]')) {
            if (e.shiftKey) p.next(); else p.skip(p.isPodcast ? 15 : 5);
        } else if (key === 'ArrowLeft' && !t.closest('[role="slider"]')) {
            if (e.shiftKey) p.prev(); else p.skip(p.isPodcast ? -15 : -5);
        } else if (key === 'ArrowUp' && e.shiftKey) {
            p.setVolume((p.muted ? 0 : p.volume) + 0.05);
        } else if (key === 'ArrowDown' && e.shiftKey) {
            p.setVolume((p.muted ? 0 : p.volume) - 0.05);
        } else if (key === 'm' || key === 'M') {
            p.toggleMute();
            RS.toast(p.muted ? 'Som desativado' : 'Som ativado');
        } else if (key === 's' || key === 'S') {
            p.setShuffle(!p.shuffle);
            RS.toast(p.shuffle ? 'Aleatório ativado' : 'Aleatório desativado');
        } else if (key === 'r' || key === 'R') {
            p.cycleRepeat();
            RS.toast({ off: 'Repetir desligado', all: 'Repetindo a fila', one: 'Repetindo esta música' }[p.repeat]);
        } else if ((key === 'c' || key === 'C') && p.current && p.current.kind === 'musica') {
            RS.toggleLike(p.current.id);
        } else if (key === 'q' || key === 'Q') {
            RS.playerUI.openPanel('queue');
        } else if ((key === 'y' || key === 'Y') && p.current && p.current.kind === 'musica') {
            RS.playerUI.openPanel('lyrics');
        } else if ((key === 'x' || key === 'X') && p.current) {
            RS.playerUI.close();
        } else if (key === '/') {
            focusSearch();
        } else if (key === '?') {
            showShortcuts();
        } else if (key === '>' ) {
            p.cycleSpeed(1);
            if (p.isPodcast) RS.toast(`Velocidade ${p.podcastSpeed}x`);
        } else if (key === '<') {
            p.cycleSpeed(-1);
            if (p.isPodcast) RS.toast(`Velocidade ${p.podcastSpeed}x`);
        } else if (/^[0-9]$/.test(key) && p.current && !onControl) {
            p.seek((Number(key) / 10) * p.duration);
        } else {
            handled = false;
        }
        if (handled) e.preventDefault();
    }

    function focusSearch() {
        const input = document.getElementById('searchInput');
        if (input) { input.focus(); input.select(); return; }
        navigate('buscar.php').then(() => {
            const i = document.getElementById('searchInput');
            if (i) i.focus();
        });
    }

    // ===================== Cliques globais (delegação) =====================
    document.addEventListener('click', (e) => {
        const t = e.target;

        // Menu de contexto aberto: trata cliques dentro, fecha com cliques fora.
        const ctxBtn = t.closest('#ctxMenu [data-menu]');
        if (ctxBtn) { onMenuAction(ctxBtn); return; }
        if (!t.closest('#ctxMenu') && !$('ctxMenu').hidden && !t.closest('[data-action="track-menu"]')) closeMenu();
        if (!t.closest('.user-menu')) closeUserMenu();

        const like = t.closest('.like-toggle[data-like-id]');
        if (like) { e.preventDefault(); e.stopPropagation(); RS.toggleLike(like.dataset.likeId); return; }

        const actionEl = t.closest('[data-action]');
        if (actionEl) {
            const action = actionEl.dataset.action;
            const handlers = {
                'track-menu': () => {
                    const host = actionEl.closest('[data-track]');
                    if (!$('ctxMenu').hidden && menuSource === actionEl) { closeMenu(); return; }
                    if (host) openTrackMenu(host, actionEl);
                },
                'play-card': () => playFromElement(actionEl.closest('[data-track]')),
                'play-url': () => RS.playUrl(actionEl.dataset.url, { button: actionEl }),
                'play-all': () => playAll(actionEl, false),
                'shuffle-all': () => playAll(actionEl, true),
                'create-playlist': () => RS.createPlaylist(),
                'edit-playlist': () => RS.editPlaylist(actionEl),
                'delete-playlist': () => RS.deletePlaylist(Number(actionEl.dataset.id), actionEl.dataset.nome),
                'toggle-queue': () => RS.playerUI.openPanel('queue'),
                'toggle-lyrics': () => RS.playerUI.openPanel('lyrics'),
                'close-panel': () => RS.playerUI.closePanel(),
                'queue-clear': () => RS.player.clearUpcoming(),
                'player-expand': () => { if (isMobile() && RS.player.current) RS.playerUI.expand(true); },
                'player-collapse': () => history.back(),
                'history-back': () => history.back(),
                'history-forward': () => history.forward(),
                'show-shortcuts': () => showShortcuts(),
                'dismiss-alert': () => { const box = actionEl.parentElement; if (box) box.remove(); },
                'use-location': () => useLocation(actionEl),
                'forget-location': () => {
                    document.cookie = 'rs_local=; Max-Age=0; path=/; SameSite=Lax';
                    RS.toast('Localização esquecida');
                    RS.reload();
                },
                'user-menu': () => {
                    const m = $('userMenu');
                    m.hidden = !m.hidden;
                    actionEl.setAttribute('aria-expanded', String(!m.hidden));
                },
            };
            if (handlers[action]) {
                e.preventDefault();
                e.stopPropagation();
                handlers[action]();
                return;
            }
        }

        if (t.id === 'playerDislike' || t.closest('#playerDislike')) { toggleDislike(); return; }

        const shelfNav = t.closest('.shelf-nav');
        if (shelfNav) {
            const track = shelfNav.closest('.shelf').querySelector('.shelf-track');
            track.scrollBy({ left: Number(shelfNav.dataset.shelf) * track.clientWidth * 0.85, behavior: 'smooth' });
            return;
        }

        // Linha de faixa: clique em qualquer parte que não seja link/botão toca.
        const row = t.closest('.tl-row');
        if (row && !t.closest('a, button, input')) { playFromElement(row); return; }

        // Links internos: navegação sem recarregar a página.
        const link = t.closest('a[href]');
        if (link && !e.defaultPrevented && e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey) {
            if (link.target && link.target !== '_self') return;
            if (link.hasAttribute('download') || link.dataset.noSpa !== undefined) return;
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:')) return;
            const url = new URL(link.href);
            if (!isSpaUrl(url)) return;
            e.preventDefault();
            if ($('player').classList.contains('is-expanded')) RS.playerUI.expand(false);
            navigate(url.href);
        }
    });

    document.addEventListener('keydown', (e) => {
        // Enter/Espaço numa linha de faixa focada = tocar.
        const row = e.target.closest && e.target.closest('.tl-row');
        if (row && e.target === row && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            playFromElement(row);
            return;
        }
        // Navegação por setas dentro do menu de contexto.
        if (!$('ctxMenu').hidden && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
            const items = [...$('ctxMenu').querySelectorAll('.ctx-item')].filter((i) => i.offsetParent !== null);
            const i = items.indexOf(document.activeElement);
            const next = items[(i + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length];
            if (next) next.focus();
            e.preventDefault();
            return;
        }
        onKeyDown(e);
    });

    document.addEventListener('contextmenu', (e) => {
        const host = e.target.closest('.tl-row, .media-card[data-track], .episode-row[data-track]');
        if (!host) return;
        e.preventDefault();
        openTrackMenu(host, host, { x: e.clientX, y: e.clientY });
    });

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.matches('[data-api-form]')) {
            e.preventDefault();
            submitApiForm(form, e.submitter);
            return;
        }
        // Formulários GET para páginas do app (ex: busca sem JS) sem recarregar.
        if ((form.method || 'get').toLowerCase() === 'get' && !form.dataset.noSpa) {
            const url = new URL(form.getAttribute('action') || location.href, document.baseURI);
            if (!isSpaUrl(url)) return;
            e.preventDefault();
            url.search = new URLSearchParams(new FormData(form)).toString();
            navigate(url.href);
        }
    });

    // Rolar a página fecha o menu, exceto a rolagem residual logo após abrir
    // (ex: o navegador trazendo a linha clicada para a tela).
    const closeOnScroll = (e) => {
        if (Date.now() - menuOpenedAt < 400) return;
        if (e && e.target && e.target.closest && e.target.closest('#ctxMenu')) return;
        closeMenu();
    };
    window.addEventListener('resize', closeMenu, { passive: true });
    document.addEventListener('scroll', closeOnScroll, { capture: true, passive: true });

    // ===================== Inicialização de páginas =====================
    RS.pages = RS.pages || {};
    RS.initPage = function (root) {
        if (RS.commonInit) RS.commonInit(root);
        initShelves(root);
        initAds(root);
        RS.syncLikes(root);
        if (RS.playerUI) RS.playerUI.markPlayingRows();
        root.querySelectorAll('img').forEach((img) => {
            if (!img.getAttribute('onerror')) img.addEventListener('error', () => { if (!img.dataset.fallback) { img.dataset.fallback = '1'; img.src = 'Componentes/icones/icone.png'; } }, { once: true });
        });
        const init = RS.pages[root.dataset.page];
        if (init) {
            try { init(root); } catch (err) { console.error(err); }
        }
    };

    function boot() {
        initAds(document.querySelector('.sidebar') || document.createElement('div'));
        fitSidebar();
        markActiveNav();
        history.replaceState(Object.assign({}, history.state, { scroll: 0 }), '');
        RS.initPage($('app-content'));
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
