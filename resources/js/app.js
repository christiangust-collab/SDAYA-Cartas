import './bootstrap';
import {
    ClassicEditor,
    Alignment,
    Autoformat,
    BlockQuote,
    Bold,
    Essentials,
    FontBackgroundColor,
    FontColor,
    FontFamily,
    FontSize,
    Heading,
    HorizontalLine,
    Image,
    ImageCaption,
    ImageResize,
    ImageStyle,
    ImageToolbar,
    ImageUpload,
    Indent,
    IndentBlock,
    Italic,
    Link,
    List,
    ListProperties,
    Paragraph,
    PasteFromOffice,
    RemoveFormat,
    Strikethrough,
    Table,
    TableCaption,
    TableCellProperties,
    TableColumnResize,
    TableProperties,
    TableToolbar,
    Underline,
    Undo,
} from 'ckeditor5';
import { PageBreak } from '@ckeditor/ckeditor5-page-break';
import { GeneralHtmlSupport } from '@ckeditor/ckeditor5-html-support';
import 'ckeditor5/ckeditor5.css';
import '@ckeditor/ckeditor5-page-break/dist/index.css';

document.documentElement.classList.add('js');

const select = (selector, parent = document) => parent.querySelector(selector);
const selectAll = (selector, parent = document) => [...parent.querySelectorAll(selector)];

function setupMobileMenu() {
    const sidebar = select('#sidebar');
    const overlay = select('#mobile-overlay');
    const opener = select('[data-menu-open]');

    if (!sidebar || !overlay || !opener) return;

    const desktop = window.matchMedia('(min-width: 1024px)');
    const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
    let isOpen = false;

    const syncAccessibility = () => {
        const hiddenFromUser = !desktop.matches && !isOpen;
        sidebar.toggleAttribute('inert', hiddenFromUser);
        sidebar.setAttribute('aria-hidden', String(hiddenFromUser));
    };

    const setOpen = (open) => {
        isOpen = open;
        sidebar.classList.toggle('-translate-x-full', !open);
        overlay.classList.toggle('hidden', !open);
        opener.setAttribute('aria-expanded', String(open));
        document.body.classList.toggle('overflow-hidden', open);
        if (open) select('[data-menu-close]', sidebar)?.focus();
        else opener.focus();
        syncAccessibility();
    };

    opener.addEventListener('click', () => setOpen(true));
    selectAll('[data-menu-close]').forEach((button) => button.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && opener.getAttribute('aria-expanded') === 'true') setOpen(false);
        if (event.key !== 'Tab' || !isOpen || desktop.matches) return;

        const focusable = selectAll(focusableSelector, sidebar).filter((element) => element.offsetParent !== null);
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
    desktop.addEventListener('change', (event) => {
        if (event.matches) {
            isOpen = false;
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            opener.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('overflow-hidden');
        }
        syncAccessibility();
    });
    syncAccessibility();
}

function setupSidebarCollapse() {
    const toggle = select('[data-sidebar-toggle]');
    if (!toggle) return;

    const sync = () => {
        const collapsed = document.documentElement.classList.contains('sidebar-collapsed');
        toggle.setAttribute('aria-pressed', String(collapsed));
        toggle.setAttribute('aria-label', collapsed ? 'Expandir navegación' : 'Contraer navegación');
        toggle.title = collapsed ? 'Expandir navegación' : 'Contraer navegación';
    };

    toggle.addEventListener('click', () => {
        const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        try {
            window.localStorage.setItem('sdaya-sidebar', collapsed ? 'collapsed' : 'expanded');
        } catch {
            // El estado visual funciona aunque el navegador bloquee el almacenamiento local.
        }
        sync();
    });

    sync();
}

function setupPasswordVisibility() {
    selectAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        if (!input) return;

        const showIcon = select('[data-password-show]', button);
        const hideIcon = select('[data-password-hide]', button);

        button.addEventListener('click', () => {
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(reveal));
            button.setAttribute('aria-label', reveal ? 'Ocultar contraseña' : 'Mostrar contraseña');
            showIcon?.classList.toggle('hidden', reveal);
            hideIcon?.classList.toggle('hidden', !reveal);
            input.focus();
        });
    });
}

function setupFlashMessages() {
    selectAll('.flash-dismiss').forEach((button) => {
        button.addEventListener('click', () => button.closest('.flash-message')?.remove());
    });
}

function setupConfirmations() {
    selectAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            if (!window.confirm(element.dataset.confirm)) event.preventDefault();
        });
    });
}

function setupValidationFeedback() {
    const summary = select('[data-validation-summary]');
    if (summary) summary.focus();

    selectAll('[id$="-error"]').forEach((error) => {
        if (!error.textContent.trim()) return;
        const field = select(`[aria-describedby~="${CSS.escape(error.id)}"]`);
        field?.setAttribute('aria-invalid', 'true');
    });
}

function setupTabs() {
    selectAll('[data-tabs]').forEach((tabs) => {
        const buttons = selectAll('[role="tab"]', tabs);
        const panels = selectAll('[role="tabpanel"]', tabs);

        const activate = (name, focus = false) => {
            buttons.forEach((button) => {
                const active = button.dataset.tab === name;
                button.setAttribute('aria-selected', String(active));
                button.tabIndex = active ? 0 : -1;
                if (active && focus) button.focus();
            });
            panels.forEach((panel) => {
                const isCurrent = panel.dataset.tabPanel === name;
                panel.hidden = !isCurrent;
                panel.classList.toggle('hidden', !isCurrent);
            });
            if (history.replaceState) history.replaceState(null, '', `#${name}`);
        };

        const initial = panels.some((panel) => `#${panel.dataset.tabPanel}` === window.location.hash)
            ? window.location.hash.slice(1)
            : buttons[0]?.dataset.tab;
        if (initial) activate(initial);

        buttons.forEach((button, index) => {
            button.addEventListener('click', () => activate(button.dataset.tab));
            button.addEventListener('keydown', (event) => {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                let target = index;
                if (event.key === 'ArrowLeft') target = (index - 1 + buttons.length) % buttons.length;
                if (event.key === 'ArrowRight') target = (index + 1) % buttons.length;
                if (event.key === 'Home') target = 0;
                if (event.key === 'End') target = buttons.length - 1;
                activate(buttons[target].dataset.tab, true);
            });
        });
    });
}

/**
 * Adaptador de subida Base64 seguro para imágenes en CKEditor 5.
 * Garantiza 1 imagen pegada = 1 imagen insertada, comprimida proporcionalmente.
 */
const IMAGEN_MAXIMA_LADO = 1200;
const IMAGEN_PESO_MAXIMO = 8 * 1024 * 1024;

function leerArchivoComoDataUrl(archivo) {
    return new Promise((resolver, rechazar) => {
        const lector = new FileReader();
        lector.onload = () => resolver(lector.result);
        lector.onerror = () => rechazar(new Error('No se pudo leer la imagen.'));
        lector.readAsDataURL(archivo);
    });
}

function cargarImagenDesdeDataUrl(dataUrl) {
    return new Promise((resolver, rechazar) => {
        const imagen = new window.Image();
        imagen.onload = () => resolver(imagen);
        imagen.onerror = () => rechazar(new Error('La imagen no es válida.'));
        imagen.src = dataUrl;
    });
}

async function procesarYComprimirImagen(archivo) {
    if (archivo.size > IMAGEN_PESO_MAXIMO) {
        throw new Error('La imagen supera el máximo de 8 MB.');
    }

    const dataUrl = await leerArchivoComoDataUrl(archivo);
    if (archivo.type === 'image/gif') return dataUrl;

    const img = await cargarImagenDesdeDataUrl(dataUrl);
    const escala = Math.min(1, IMAGEN_MAXIMA_LADO / (img.naturalWidth || 1), IMAGEN_MAXIMA_LADO / (img.naturalHeight || 1));
    const ancho = Math.max(1, Math.round((img.naturalWidth || 1) * escala));
    const alto = Math.max(1, Math.round((img.naturalHeight || 1) * escala));

    const lienzo = document.createElement('canvas');
    lienzo.width = ancho;
    lienzo.height = alto;

    const ctx = lienzo.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, ancho, alto);
    ctx.drawImage(img, 0, 0, ancho, alto);

    if (archivo.type === 'image/png' && dataUrl.length < 400_000 && escala === 1) {
        return dataUrl;
    }

    return lienzo.toDataURL('image/jpeg', 0.85);
}

class SDAYAUploadAdapter {
    constructor(loader) {
        this.loader = loader;
    }

    upload() {
        return this.loader.file
            .then((file) => procesarYComprimirImagen(file))
            .then((dataUrl) => ({ default: dataUrl }));
    }

    abort() {}
}

function SDAYAUploadAdapterPlugin(editor) {
    editor.plugins.get('FileRepository').createUploadAdapter = (loader) => new SDAYAUploadAdapter(loader);
}

/**
 * Selector de alineación del encabezado y pie de firma.
 * Sincroniza los botones con los inputs hidden, CKEditor y tarjeta de preview.
 */
function setupAlineacionEncabezado(editor = null) {
    const grupos = selectAll('[data-align-group]');

    if (!grupos.length) {
        // Soporte fallback para markup previo
        const botones = selectAll('.align-btn');
        const campo = select('#alineacion_encabezado');
        if (!botones.length || !campo) return;

        botones.forEach((btn) => {
            btn.addEventListener('click', () => {
                const valor = btn.dataset.align;
                if (!valor) return;
                campo.value = valor;
                botones.forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.align === valor)));
            });
        });
        return;
    }

    grupos.forEach((grupo) => {
        const nombreCampo = grupo.dataset.alignGroup;
        const campo = select(`#${nombreCampo}`);
        const botones = selectAll('.align-btn', grupo);
        if (!campo || !botones.length) return;

        botones.forEach((btn) => {
            btn.addEventListener('click', () => {
                const valor = btn.dataset.align;
                if (!valor) return;

                campo.value = valor;
                botones.forEach((b) => {
                    b.setAttribute('aria-pressed', String(b.dataset.align === valor));
                });

                // Si es el encabezado, actualizar widget en CKEditor
                if (nombreCampo === 'alineacion_encabezado' && editor && typeof editor.getData === 'function') {
                    const data = editor.getData();
                    if (data.includes('data-sdaya-meta')) {
                        const actualizado = data.replace(/class="sdaya-meta sdaya-align-(?:left|center|right)"/g, `class="sdaya-meta sdaya-align-${valor}"`);
                        editor.setData(actualizado);
                    }
                }

                // Si es el pie de firma, actualizar en vivo la tarjeta de vista previa
                if (nombreCampo === 'alineacion_pie_firma') {
                    const card = select('[data-firmante-card]');
                    if (card) {
                        card.classList.remove('text-left', 'text-center', 'text-right');
                        card.classList.add(`text-${valor}`);
                    }
                }
            });
        });
    });
}

/**
 * Importación de DOCX hacia CKEditor 5
 */
function setupImportadorDocx(editor, wrapper) {
    const botonAbrir = select('[data-importar-docx]', wrapper.closest('[data-editor-wrapper]') ?? document);
    const modal = select('#modal-importar-docx');
    const inputArchivo = select('#docx-archivo');
    const checkOmitirFirma = select('#docx-omitir-firma');
    const checkAutocompletarMeta = select('#docx-autocompletar-meta');
    const botonCancelar = select('#docx-cancelar');
    const botonConfirmar = select('#docx-confirmar');
    const errorEl = select('#docx-error');

    if (!botonAbrir || !modal || !inputArchivo || !botonConfirmar) return;

    const importarUrl = wrapper.dataset.importarUrl ?? '';
    const csrf = wrapper.dataset.csrf ?? '';

    botonAbrir.addEventListener('click', () => {
        inputArchivo.value = '';
        if (errorEl) { errorEl.textContent = ''; errorEl.classList.add('hidden'); }
        modal.showModal?.();
    });

    botonCancelar?.addEventListener('click', () => modal.close?.());
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.close?.(); });

    botonConfirmar.addEventListener('click', async () => {
        const archivo = inputArchivo.files?.[0];

        if (!archivo) {
            if (errorEl) { errorEl.textContent = 'Selecciona un archivo DOCX.'; errorEl.classList.remove('hidden'); }
            return;
        }

        botonConfirmar.disabled = true;
        botonConfirmar.textContent = 'Importando…';
        if (errorEl) { errorEl.textContent = ''; errorEl.classList.add('hidden'); }

        const omitirFirma = checkOmitirFirma ? checkOmitirFirma.checked : true;
        const autocompletarMeta = checkAutocompletarMeta ? checkAutocompletarMeta.checked : true;

        try {
            const formData = new FormData();
            formData.append('archivo', archivo);
            formData.append('omitir_firma', omitirFirma ? '1' : '0');

            const respuesta = await fetch(importarUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: formData,
            });

            const datos = await respuesta.json();

            if (!respuesta.ok) {
                const msg = datos?.errors?.archivo?.[0] ?? datos?.message ?? 'Error al procesar el archivo DOCX.';
                throw new Error(msg);
            }

            if (typeof datos.html === 'string' && editor) {
                editor.setData(datos.html);

                // Autocompletar Destinatario, Referencia y Firmante si se detectaron
                if (autocompletarMeta) {
                    const inputDestinatario = select('#destinatario');
                    const inputAsunto = select('#asunto');
                    const inputFecha = select('#fecha_documento');
                    const selectFirmante = select('#firmante_id');

                    if (inputDestinatario && datos.destinatario && (!inputDestinatario.value || inputDestinatario.value.trim() === '')) {
                        inputDestinatario.value = datos.destinatario;
                    }
                    if (inputAsunto && datos.asunto && (!inputAsunto.value || inputAsunto.value.trim() === '')) {
                        inputAsunto.value = datos.asunto;
                    }
                    if (inputFecha && datos.fecha && (!inputFecha.value || inputFecha.value.trim() === '')) {
                        inputFecha.value = datos.fecha;
                    }

                    if (selectFirmante && datos.firmante_detectado) {
                        const buscado = datos.firmante_detectado.toLowerCase();
                        for (const opt of selectFirmante.options) {
                            if (opt.value && opt.textContent.toLowerCase().includes(buscado)) {
                                selectFirmante.value = opt.value;
                                selectFirmante.dispatchEvent(new Event('change', { bubbles: true }));
                                break;
                            }
                        }
                    }
                }

                modal.close?.();
            }
        } catch (error) {
            if (errorEl) {
                errorEl.textContent = error?.message ?? 'No se pudo importar el documento Word.';
                errorEl.classList.remove('hidden');
            }
        } finally {
            botonConfirmar.disabled = false;
            botonConfirmar.textContent = 'Importar contenido';
        }
    });
}

/**
 * Inicialización de CKEditor 5
 */
let activeCKEditor = null;

async function setupEditors() {
    const wrappers = selectAll('[data-editor-wrapper]');
    if (!wrappers.length) return;

    for (const wrapper of wrappers) {
        const textarea = select('[data-editor-textarea]', wrapper);
        const mountTarget = select('[data-ckeditor-target]', wrapper);
        if (!textarea || !mountTarget) continue;

        const editor = await ClassicEditor.create(mountTarget, {
            licenseKey: 'GPL',
            plugins: [
                Essentials,
                Paragraph,
                Heading,
                Bold,
                Italic,
                Underline,
                Strikethrough,
                FontFamily,
                FontSize,
                FontColor,
                FontBackgroundColor,
                RemoveFormat,
                Alignment,
                List,
                ListProperties,
                Indent,
                IndentBlock,
                Table,
                TableToolbar,
                TableProperties,
                TableCellProperties,
                TableColumnResize,
                TableCaption,
                Image,
                ImageCaption,
                ImageResize,
                ImageStyle,
                ImageToolbar,
                ImageUpload,
                SDAYAUploadAdapterPlugin,
                HorizontalLine,
                PageBreak,
                GeneralHtmlSupport,
                Link,
                BlockQuote,
                PasteFromOffice,
                Undo,
                Autoformat,
            ],
            toolbar: {
                items: [
                    'undo', 'redo',
                    '|',
                    'fontFamily', 'fontSize',
                    '|',
                    'bold', 'italic', 'underline', 'strikethrough',
                    '|',
                    'fontColor', 'fontBackgroundColor',
                    'removeFormat',
                    '|',
                    'alignment',
                    '|',
                    'bulletedList', 'numberedList', 'outdent', 'indent',
                    '|',
                    'insertTable', 'imageUpload', 'horizontalLine', 'pageBreak', 'link', 'blockQuote',
                ],
                shouldNotGroupWhenFull: true,
            },
            fontFamily: {
                options: [
                    'default',
                    'Montserrat, sans-serif',
                    'Arial, Helvetica, sans-serif',
                    'Times New Roman, Times, serif',
                    'Georgia, serif',
                    'Courier New, Courier, monospace',
                    'Century Gothic, sans-serif',
                ],
                supportAllValues: true,
            },
            fontSize: {
                options: [10, 11, 12, 14, 16, 18, 20, 24],
                supportAllValues: true,
            },
            table: {
                contentToolbar: [
                    'tableColumn', 'tableRow', 'mergeTableCells',
                    'tableProperties', 'tableCellProperties',
                ],
            },
            image: {
                toolbar: [
                    'imageStyle:inline',
                    'imageStyle:block',
                    'imageStyle:side',
                    '|',
                    'toggleImageCaption',
                    'imageTextAlternative',
                    '|',
                    'imageStyle:alignLeft',
                    'imageStyle:alignCenter',
                    'imageStyle:alignRight',
                ],
                styles: [
                    'alignLeft',
                    'alignCenter',
                    'alignRight',
                ],
            },
            htmlSupport: {
                allow: [
                    {
                        name: /.*/,
                        attributes: true,
                        classes: true,
                        styles: true,
                    },
                ],
            },
            initialData: textarea.value || '',
        });

        activeCKEditor = editor;
        window.CKEditorInstance = editor;
        window.SDAYA = {
            CKEditor: editor,
            setupAlineacionEncabezado,
            setupImportadorDocx,
        };

        // Sincronizar cambios hacia el textarea
        editor.model.document.on('change:data', () => {
            textarea.value = editor.getData();
        });

        textarea.form?.addEventListener('submit', () => {
            textarea.value = editor.getData();
        });

        setupAlineacionEncabezado(editor);
        setupImportadorDocx(editor, wrapper);
    }
}

function setupCitePreview() {
    const form = select('[data-document-form]');
    if (!form) return;

    const area = select('[name="area_id"]', form);
    const tipo = select('[name="tipo_id"]', form);
    const fecha = select('[name="fecha_documento"]', form);
    const output = select('[data-cite-preview]', form);
    const message = select('[data-cite-message]', form);
    let controller;
    let timer;

    output?.setAttribute('aria-live', 'polite');

    const update = async () => {
        const year = fecha?.value?.slice(0, 4);
        if (!area?.value || !tipo?.value || !year) {
            if (output) output.textContent = 'Selecciona área, tipo y fecha';
            return;
        }

        controller?.abort();
        controller = new AbortController();
        if (output) output.textContent = 'Calculando…';
        const params = new URLSearchParams({ area: area.value, tipo: tipo.value, anio: year });

        try {
            const response = await fetch(`${form.dataset.previewUrl}?${params}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('No se pudo calcular');
            const body = await response.json();
            if (output) output.textContent = body.data.cite;
            if (message) message.textContent = body.data.mensaje;
        } catch (error) {
            if (error.name === 'AbortError') return;
            if (output) output.textContent = 'Vista previa no disponible';
            if (message) message.textContent = 'Puedes guardar el borrador; el CITE se confirmará al emitir.';
        }
    };

    [area, tipo, fecha].forEach((field) => field?.addEventListener('change', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(update, 180);
    }));
    update();
}

function setupFirmantePreview() {
    const selectFirmante = select('[data-firmante-select]');
    if (!selectFirmante) return;

    const nombreEl = select('[data-firmante-nombre-preview]');
    const cargoEl = select('[data-firmante-cargo-preview]');
    const empresaEl = select('[data-firmante-empresa-preview]');
    const telefonoEl = select('[data-firmante-telefono-preview]');
    const correoEl = select('[data-firmante-correo-preview]');
    const rubricaWrapper = select('[data-firmante-rubrica-wrapper]');
    const rubricaImg = select('[data-firmante-rubrica-img]');

    const update = () => {
        const option = selectFirmante.selectedOptions[0];
        if (!option) return;

        const nombre = option.dataset.nombre || option.textContent.trim();
        const cargo = option.dataset.cargo || '';
        const empresa = option.dataset.empresa || '';
        const correo = option.dataset.correo || '';
        const telefono = option.dataset.telefono || '';
        const firma = option.dataset.firma || '';
        const empresaId = option.dataset.empresaId || '';

        const hiddenEmpresaId = document.getElementById('documento_empresa_id');
        if (hiddenEmpresaId && empresaId) {
            hiddenEmpresaId.value = empresaId;
        }

        if (nombreEl) nombreEl.textContent = nombre;
        if (cargoEl) {
            cargoEl.textContent = cargo ? cargo.toUpperCase() : '';
            cargoEl.style.display = cargo ? 'block' : 'none';
        }
        if (empresaEl) {
            empresaEl.textContent = empresa ? empresa.toUpperCase() : '';
            empresaEl.style.display = empresa ? 'block' : 'none';
        }
        if (telefonoEl) {
            telefonoEl.innerHTML = telefono ? `<span class="font-bold">móvil:</span> ${telefono}` : '';
            telefonoEl.style.display = telefono ? 'block' : 'none';
        }
        if (correoEl) {
            correoEl.innerHTML = correo ? `<span class="font-bold">email:</span> ${correo}` : '';
            correoEl.style.display = correo ? 'block' : 'none';
        }

        if (rubricaWrapper && rubricaImg) {
            if (firma) {
                rubricaImg.src = firma;
                rubricaWrapper.classList.remove('hidden');
            } else {
                rubricaImg.src = '';
                rubricaWrapper.classList.add('hidden');
            }
        }
    };

    selectFirmante.addEventListener('change', update);
    update();
}

setupMobileMenu();
setupSidebarCollapse();
setupPasswordVisibility();
setupFlashMessages();
setupConfirmations();
setupValidationFeedback();
setupTabs();
setupAlineacionEncabezado();
setupCitePreview();
setupFirmantePreview();
setupEditors().catch((error) => {
    console.error('[SDAYA Editor] Error inicializando CKEditor 5:', error);
});
