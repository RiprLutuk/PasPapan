import "./bootstrap";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";
import flatpickr from "flatpickr";
import Swal from "sweetalert2";
import Chart from "chart.js/auto";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import "leaflet.markercluster";
import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

window.L = L;
window.TomSelect = TomSelect;
window.flatpickr = flatpickr;
window.Swal = Swal;
window.Chart = Chart;

const pasPapanAlertLabels = () => ({
    confirm: window.PasPapanAlertLabels?.confirm || "Confirm",
    cancel: window.PasPapanAlertLabels?.cancel || "Cancel",
    confirmTitle: window.PasPapanAlertLabels?.confirmTitle || "Are you sure?",
    noticeTitle: window.PasPapanAlertLabels?.noticeTitle || "Notice",
});

const normalizeSweetAlertIcon = (icon) => {
    if (["danger", "failed", "failure"].includes(icon)) {
        return "error";
    }

    if (["warn"].includes(icon)) {
        return "warning";
    }

    return ["success", "error", "warning", "info", "question"].includes(icon)
        ? icon
        : "info";
};

const sweetAlertBaseClasses = {
    popup: "!w-[min(28rem,calc(100vw-2rem))] !rounded-[1.35rem] !border !border-slate-200 !bg-white !px-5 !py-6 !text-slate-950 !shadow-[0_28px_80px_-42px_rgba(15,23,42,0.62)] dark:!border-slate-700 dark:!bg-slate-900 dark:!text-white",
    icon: "!my-2 !h-16 !w-16 !border-[0.28rem]",
    title: "!mt-4 !text-lg !font-bold !tracking-tight",
    htmlContainer: "!mx-0 !mt-3 !text-sm !leading-6 !text-slate-600 dark:!text-slate-300",
    actions: "!mt-7 !grid !w-full !grid-cols-1 !gap-3 sm:!grid-cols-2",
    confirmButton: "!m-0 !inline-flex !min-h-[3rem] !w-full !items-center !justify-center !rounded-2xl !bg-primary-700 !px-5 !py-3 !text-base !font-bold !text-white hover:!bg-primary-800 focus:!shadow-none focus:!ring-2 focus:!ring-primary-500 focus:!ring-offset-2 dark:!bg-primary-400 dark:!text-slate-950 dark:hover:!bg-primary-300",
    cancelButton: "!m-0 !inline-flex !min-h-[3rem] !w-full !items-center !justify-center !rounded-2xl !border !border-slate-300 !bg-transparent !px-5 !py-3 !text-base !font-bold !text-slate-700 hover:!bg-slate-50 focus:!shadow-none focus:!ring-2 focus:!ring-slate-300 focus:!ring-offset-2 dark:!border-slate-700 dark:!text-slate-100 dark:hover:!bg-slate-800",
};

const createPasPapanToast = () => Swal.mixin({
    toast: true,
    position: "bottom-end",
    showConfirmButton: false,
    timer: 3200,
    timerProgressBar: true,
    background: "transparent",
    customClass: {
        popup: "!bg-white dark:!bg-slate-900 !text-slate-900 dark:!text-white !rounded-2xl !shadow-[0_18px_48px_-28px_rgba(15,23,42,0.65)] !border !border-slate-100 dark:!border-slate-700/70 !px-4 !py-3 !w-auto !max-w-[92vw] !mx-auto !mt-4",
        title: "!text-sm !font-semibold !leading-5",
        timerProgressBar: "!bg-primary-500 !h-1",
    },
    didOpen: (toast) => {
        toast.addEventListener("mouseenter", Swal.stopTimer);
        toast.addEventListener("mouseleave", Swal.resumeTimer);
    },
});

window.PasPapanAlert = {
    toast(payload = {}) {
        const title = payload.title || payload.message || payload.text || "";

        if (!title) {
            return Promise.resolve();
        }

        return createPasPapanToast().fire({
            icon: normalizeSweetAlertIcon(payload.icon || payload.style || "info"),
            title,
        });
    },
    modal(payload = {}) {
        const labels = pasPapanAlertLabels();

        return Swal.fire({
            icon: normalizeSweetAlertIcon(payload.icon || "info"),
            title: payload.title || labels.noticeTitle,
            text: payload.text || payload.message || "",
            confirmButtonText: payload.confirmButtonText || labels.confirm,
            buttonsStyling: false,
            customClass: sweetAlertBaseClasses,
        });
    },
    async confirm(message, options = {}) {
        const labels = pasPapanAlertLabels();
        const result = await Swal.fire({
            icon: normalizeSweetAlertIcon(options.icon || "question"),
            title: options.title || labels.confirmTitle,
            text: message || options.text || "",
            showCancelButton: true,
            confirmButtonText: options.confirmButtonText || labels.confirm,
            cancelButtonText: options.cancelButtonText || labels.cancel,
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: false,
            customClass: sweetAlertBaseClasses,
        });

        return result.isConfirmed;
    },
};

window.Toast = createPasPapanToast();

window.alert = (message) => {
    window.PasPapanAlert.modal({
        icon: "info",
        title: String(message || pasPapanAlertLabels().noticeTitle),
    });
};

const installSweetAlertConfirmations = (root = document) => {
    root.querySelectorAll?.("[wire\\:confirm], [wire\\:confirm\\.prompt]").forEach((element) => {
        element.__livewire_confirm = (onConfirm, onCancel) => {
            const message = element.getAttribute("wire:confirm")
                || element.getAttribute("wire:confirm.prompt")
                || pasPapanAlertLabels().confirmTitle;

            window.PasPapanAlert.confirm(message).then((confirmed) => {
                if (confirmed) {
                    onConfirm();
                    return;
                }

                onCancel();
            });
        };
    });
};

const scheduleSweetAlertConfirmations = (root = document) => {
    installSweetAlertConfirmations(root);
    setTimeout(() => installSweetAlertConfirmations(root), 0);
    setTimeout(() => installSweetAlertConfirmations(root), 80);
};

const installSweetAlertDelegates = () => {
    document.addEventListener("click", async (event) => {
        const trigger = event.target.closest("[data-sweet-confirm]");

        if (!trigger) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const confirmed = await window.PasPapanAlert.confirm(
            trigger.dataset.confirmMessage,
            {
                icon: trigger.dataset.confirmIcon || "warning",
                title: trigger.dataset.confirmTitle,
                confirmButtonText: trigger.dataset.confirmButton,
                cancelButtonText: trigger.dataset.cancelButton,
            },
        );

        if (!confirmed) {
            return;
        }

        const formId = trigger.dataset.confirmForm;
        const form = formId ? document.getElementById(formId) : trigger.closest("form");

        if (form) {
            form.requestSubmit();
        }
    });

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    scheduleSweetAlertConfirmations(node);
                }
            });
        });
    });

    if (document.body) {
        observer.observe(document.body, { childList: true, subtree: true });
    }

    scheduleSweetAlertConfirmations();
};

document.addEventListener("DOMContentLoaded", installSweetAlertDelegates);
document.addEventListener("livewire:init", () => {
    scheduleSweetAlertConfirmations();
});
document.addEventListener("livewire:navigated", () => {
    scheduleSweetAlertConfirmations();
});

const pasPapanValidationLabels = () => ({
    required: window.PasPapanValidationLabels?.required || "Please complete this field.",
    email: window.PasPapanValidationLabels?.email || "Please enter a valid email address.",
    url: window.PasPapanValidationLabels?.url || "Please enter a valid URL.",
    pattern: window.PasPapanValidationLabels?.pattern || "Please match the requested format.",
    minLength: window.PasPapanValidationLabels?.minLength || "Please enter at least :min characters.",
    maxLength: window.PasPapanValidationLabels?.maxLength || "Please enter no more than :max characters.",
    rangeUnderflow: window.PasPapanValidationLabels?.rangeUnderflow || "Please enter a value greater than or equal to :min.",
    rangeOverflow: window.PasPapanValidationLabels?.rangeOverflow || "Please enter a value less than or equal to :max.",
    invalid: window.PasPapanValidationLabels?.invalid || "Please review this field.",
    summary: window.PasPapanValidationLabels?.summary || "Please review the highlighted fields before continuing.",
});

const interpolateValidationMessage = (message, replacements = {}) => Object.entries(replacements)
    .reduce((current, [key, value]) => current.replaceAll(`:${key}`, value), message);

const validationMessageFor = (control) => {
    const labels = pasPapanValidationLabels();
    const validity = control.validity;

    if (validity.valueMissing) {
        return labels.required;
    }

    if (validity.typeMismatch && control.type === "email") {
        return labels.email;
    }

    if (validity.typeMismatch && control.type === "url") {
        return labels.url;
    }

    if (validity.patternMismatch) {
        return labels.pattern;
    }

    if (validity.tooShort) {
        return interpolateValidationMessage(labels.minLength, { min: control.minLength });
    }

    if (validity.tooLong) {
        return interpolateValidationMessage(labels.maxLength, { max: control.maxLength });
    }

    if (validity.rangeUnderflow) {
        return interpolateValidationMessage(labels.rangeUnderflow, { min: control.min });
    }

    if (validity.rangeOverflow) {
        return interpolateValidationMessage(labels.rangeOverflow, { max: control.max });
    }

    return labels.invalid;
};

const validationTargetFor = (control) => {
    if (control.tomselect?.wrapper) {
        return control.tomselect.wrapper;
    }

    return control.closest(".flatpickr-wrapper")
        || control.closest(".auth-input-wrap, .user-native-field__control, .wfh-native-field__control")
        || control;
};

const validationContainerFor = (control) => control.closest(
    ".auth-field, .auth-check, .user-native-field, .wfh-native-field, .user-form-field, .profile-field-group, .space-y-1, .space-y-1\\.5, .space-y-2, label",
) || validationTargetFor(control).parentElement || control.parentElement;

const validationErrorIdFor = (control) => {
    if (!control.id) {
        control.id = `ui-field-${Math.random().toString(36).slice(2, 10)}`;
    }

    return `${control.id}-client-error`;
};

const clearControlValidation = (control) => {
    const errorId = validationErrorIdFor(control);
    const target = validationTargetFor(control);

    target.classList.remove("ui-invalid");
    control.classList.remove("ui-invalid");
    control.removeAttribute("aria-invalid");

    const describedBy = (control.getAttribute("aria-describedby") || "")
        .split(/\s+/)
        .filter((item) => item && item !== errorId);

    if (describedBy.length) {
        control.setAttribute("aria-describedby", describedBy.join(" "));
    } else {
        control.removeAttribute("aria-describedby");
    }

    document.getElementById(errorId)?.remove();
};

const renderControlValidation = (control) => {
    const errorId = validationErrorIdFor(control);
    const target = validationTargetFor(control);
    const container = validationContainerFor(control);
    const message = validationMessageFor(control);

    target.classList.add("ui-invalid");
    control.classList.add("ui-invalid");
    control.setAttribute("aria-invalid", "true");

    const describedBy = new Set((control.getAttribute("aria-describedby") || "").split(/\s+/).filter(Boolean));
    describedBy.add(errorId);
    control.setAttribute("aria-describedby", Array.from(describedBy).join(" "));

    let error = document.getElementById(errorId);
    if (!error) {
        error = document.createElement("p");
        error.id = errorId;
        error.className = "ui-validation-error";
        error.setAttribute("role", "alert");
        container.appendChild(error);
    }

    error.textContent = message;
};

const uiValidatableControls = (form) => Array.from(form.querySelectorAll("input, select, textarea"))
    .filter((control) => (
        !control.disabled
        && control.type !== "hidden"
        && !control.matches("[data-ui-validation-ignore]")
    ));

const validateUiForm = (form, event = null) => {
    const invalidControls = uiValidatableControls(form).filter((control) => !control.validity.valid);

    uiValidatableControls(form).forEach((control) => {
        if (control.validity.valid) {
            clearControlValidation(control);
        }
    });

    invalidControls.forEach(renderControlValidation);

    if (!invalidControls.length) {
        return true;
    }

    event?.preventDefault();
    event?.stopImmediatePropagation();

    const firstInvalid = invalidControls[0];
    const target = validationTargetFor(firstInvalid);
    target.scrollIntoView({ behavior: "smooth", block: "center" });

    requestAnimationFrame(() => {
        if (firstInvalid.tomselect) {
            firstInvalid.tomselect.focus();
            return;
        }

        firstInvalid.focus({ preventScroll: true });
    });

    window.PasPapanAlert?.toast({
        icon: "warning",
        title: pasPapanValidationLabels().summary,
    });

    return false;
};

const installUiValidationGuards = () => {
    if (!document.body?.matches(".guest-ui, .user-ui")) {
        return;
    }

    document.querySelectorAll("form:not([data-ui-validation-bound])").forEach((form) => {
        if (form.matches("[data-native-validation='true']")) {
            return;
        }

        form.dataset.uiValidationBound = "true";
        form.noValidate = true;

        form.addEventListener("submit", (event) => {
            if (event.submitter?.formNoValidate || event.defaultPrevented) {
                return;
            }

            validateUiForm(form, event);
        }, true);

        form.addEventListener("input", (event) => {
            const control = event.target;

            if (control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement) {
                if (control.validity.valid) {
                    clearControlValidation(control);
                }
            }
        }, true);

        form.addEventListener("change", (event) => {
            const control = event.target;

            if (control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement) {
                if (control.validity.valid) {
                    clearControlValidation(control);
                } else if (control.getAttribute("aria-invalid") === "true") {
                    renderControlValidation(control);
                }
            }
        }, true);
    });
};

document.addEventListener("DOMContentLoaded", installUiValidationGuards);
document.addEventListener("livewire:init", installUiValidationGuards);
document.addEventListener("livewire:navigated", installUiValidationGuards);
document.addEventListener("livewire:updated", installUiValidationGuards);

const syncUiPickerWidth = (instance, input) => {
    const wrapper = instance?.altInput?.closest?.(".flatpickr-wrapper")
        || instance?.input?.closest?.(".flatpickr-wrapper")
        || input?.closest?.(".flatpickr-wrapper");

    if (wrapper) {
        wrapper.style.display = "block";
        wrapper.style.width = "100%";
    }

    [instance?.altInput, instance?.input, input].forEach((field) => {
        if (field) {
            field.style.display = "block";
            field.style.width = "100%";
        }
    });
};

const normalizeUiPickerCalendar = (instance) => {
    const calendar = instance?.calendarContainer;

    if (!calendar) {
        return;
    }

    if (instance.input?.closest?.(".user-ui")) {
        calendar.classList.add("flatpickr-calendar--user");
    }

    const months = calendar.querySelector(".flatpickr-months");
    const month = calendar.querySelector(".flatpickr-month");
    const currentMonth = calendar.querySelector(".flatpickr-current-month");
    const monthSelect = calendar.querySelector(".flatpickr-monthDropdown-months");
    const yearWrapper = calendar.querySelector(".numInputWrapper");
    const yearInput = calendar.querySelector("input.cur-year");
    const weekdays = calendar.querySelector(".flatpickr-weekdays");
    const navButtons = calendar.querySelectorAll(".flatpickr-prev-month, .flatpickr-next-month");

    if (months) {
        months.style.height = "48px";
        months.style.minHeight = "48px";
        months.style.overflow = "visible";
        months.style.marginBottom = "2px";
    }

    if (month) {
        month.style.height = "48px";
        month.style.overflow = "visible";
        month.style.lineHeight = "1";
    }

    if (currentMonth) {
        currentMonth.style.top = "6px";
        currentMonth.style.height = "36px";
        currentMonth.style.padding = "0";
        currentMonth.style.display = "flex";
        currentMonth.style.alignItems = "center";
        currentMonth.style.justifyContent = "center";
        currentMonth.style.overflow = "visible";
        currentMonth.style.lineHeight = "1";
        currentMonth.style.fontSize = "16px";
        currentMonth.style.fontWeight = "600";
    }

    [monthSelect, yearWrapper, yearInput].forEach((element) => {
        if (!element) {
            return;
        }

        element.style.height = "36px";
        element.style.minHeight = "36px";
        element.style.lineHeight = "36px";
        element.style.overflow = "visible";
        element.style.marginTop = "0";
        element.style.marginBottom = "0";
        element.style.fontSize = "16px";
        element.style.fontWeight = "600";
    });

    if (weekdays) {
        weekdays.style.marginTop = "0";
    }

    navButtons.forEach((button) => {
        const setButtonColor = (color, background = "transparent") => {
            button.style.setProperty("background", background, "important");
            button.style.setProperty("color", color, "important");
            button.style.setProperty("fill", color, "important");

            button.querySelectorAll("svg, path").forEach((node) => {
                node.style.setProperty("color", color, "important");
                node.style.setProperty("fill", color, "important");
                node.style.setProperty("stroke", color, "important");
            });
        };

        if (!button.dataset.uiPickerNavColorReady) {
            button.dataset.uiPickerNavColorReady = "true";
            button.addEventListener("mouseenter", () => setButtonColor("#2f6f2b", "rgb(106 180 91 / 0.12)"));
            button.addEventListener("focus", () => setButtonColor("#2f6f2b", "rgb(106 180 91 / 0.12)"));
            button.addEventListener("pointerdown", () => setButtonColor("#2f6f2b", "rgb(106 180 91 / 0.12)"));
            button.addEventListener("mouseleave", () => setButtonColor("#6b7280"));
            button.addEventListener("blur", () => setButtonColor("#6b7280"));
            button.addEventListener("pointerup", () => setButtonColor("#2f6f2b", "rgb(106 180 91 / 0.12)"));
        }

        setButtonColor("#6b7280");
    });
};

const uiPickerValue = (input) => input?.value || input?.getAttribute?.("value") || "";

const uiPickerTargetValue = (selector) => {
    if (!selector) {
        return "";
    }

    const target = document.querySelector(selector);

    return target?.value || target?.getAttribute?.("value") || "";
};

const uiPickerFormatDate = (date, format = "Y-m-d") => {
    if (!date || !window.flatpickr) {
        return "";
    }

    return window.flatpickr.formatDate(date, format);
};

const syncUiRangeTargets = (input, selectedDates = []) => {
    const fromTarget = document.querySelector(input.dataset.uiRangeFrom || "");
    const toTarget = document.querySelector(input.dataset.uiRangeTo || "");
    const format = input.dataset.uiRangeFormat || "Y-m-d";
    const from = selectedDates[0] ? uiPickerFormatDate(selectedDates[0], format) : "";
    const to = selectedDates[1] ? uiPickerFormatDate(selectedDates[1], format) : from;
    const display = from && to && from !== to ? `${from} - ${to}` : from;

    if (fromTarget) {
        dispatchUiPickerValue(fromTarget, from);
    }

    if (toTarget) {
        dispatchUiPickerValue(toTarget, to);
    }

    dispatchUiPickerValue(input, display);
};

const shouldAutoCloseUiPicker = (input, mode, selectedDates = [], dateStr = "") => {
    if (input.dataset.uiPickerAutoClose === "false") {
        return false;
    }

    if (mode === "date-range") {
        return selectedDates.length >= 2;
    }

    if (mode === "time" || mode === "datetime") {
        return Boolean(dateStr);
    }

    return selectedDates.length > 0 || Boolean(dateStr);
};

const closeUiPickerAfterSelection = (input, mode, selectedDates = [], dateStr = "", instance = null) => {
    if (!instance?.isOpen || !shouldAutoCloseUiPicker(input, mode, selectedDates, dateStr)) {
        return;
    }

    window.setTimeout(() => {
        if (!instance.isOpen) {
            return;
        }

        instance.close();
        (instance.altInput || instance.input || input)?.blur?.();
        input.blur?.();
    }, mode === "time" || mode === "datetime" ? 120 : 80);
};

const isUiPickerDetached = (instance) => {
    if (!instance) {
        return true;
    }

    if (!instance.input || !instance._input || !instance.calendarContainer) {
        return true;
    }

    if (!document.documentElement.contains(instance.input)) {
        return true;
    }

    if (instance.config?.altInput && !document.documentElement.contains(instance.altInput)) {
        return true;
    }

    return false;
};

const destroyUiPicker = (input) => {
    if (!input?._flatpickr) {
        if (input && input._flatpickr === null) {
            try {
                delete input._flatpickr;
            } catch (_error) {
                input._flatpickr = undefined;
            }
        }

        return;
    }

    try {
        input._flatpickr.destroy();
    } catch (_error) {
        // Livewire can remove generated Flatpickr nodes before destroy runs.
    }

    try {
        delete input._flatpickr;
    } catch (_error) {
        input._flatpickr = undefined;
    }
};

const syncUiPickerValue = (instance, input) => {
    if (input.dataset.uiPicker === "date-range") {
        const from = uiPickerTargetValue(input.dataset.uiRangeFrom);
        const to = uiPickerTargetValue(input.dataset.uiRangeTo);
        const dates = [from, to].filter(Boolean);

        if (instance?.input && dates.length > 0) {
            instance.setDate(dates, false, instance.config?.dateFormat);
        }

        return;
    }

    const value = uiPickerValue(input);

    if (!instance?.input || !value || instance.input.value === value) {
        return;
    }

    instance.setDate(value, false, instance.config?.dateFormat);
};

const dispatchUiPickerValue = (input, value = null) => {
    if (value !== null) {
        input.value = value;
        input.setAttribute("value", value);
    }

    input.dispatchEvent(new Event("input", { bubbles: true }));
    input.dispatchEvent(new Event("change", { bubbles: true }));

    window.requestAnimationFrame(() => {
        input.dispatchEvent(new Event("input", { bubbles: true }));
        input.dispatchEvent(new Event("change", { bubbles: true }));
    });
};

const enforceUiPickerOnlyInput = (instance, input) => {
    [input, instance?.input, instance?.altInput].forEach((field) => {
        if (!field) {
            return;
        }

        if (field === instance?.altInput) {
            field.dataset.uiPickerGenerated = "true";
            field.removeAttribute("data-ui-picker");
        }

        field.readOnly = true;
        field.setAttribute("readonly", "readonly");
        field.setAttribute("inputmode", "none");
        field.setAttribute("autocomplete", "off");
        field.setAttribute("aria-haspopup", "dialog");

        if (!field.dataset.uiPickerKeyboardGuardReady) {
            field.dataset.uiPickerKeyboardGuardReady = "true";
            field.addEventListener("keydown", (event) => {
                const allowedKeys = ["Tab", "Escape", "Enter", " ", "ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown"];

                if (!allowedKeys.includes(event.key)) {
                    event.preventDefault();
                }
            });
        }
    });
};

const initUiPickers = (root = document) => {
    if (!window.flatpickr) {
        return;
    }

    const inputs = [];

    if (root.matches?.("[data-ui-picker]")) {
        inputs.push(root);
    }

    root.querySelectorAll?.("[data-ui-picker]").forEach((input) => {
        inputs.push(input);
    });

    inputs.forEach((input) => {
        if (input.dataset.uiPickerGenerated === "true" || input.classList.contains("flatpickr-mobile")) {
            return;
        }

        if (input._flatpickr === null) {
            destroyUiPicker(input);
        }

        const mode = input.dataset.uiPicker;
        const dialogPanel = input.closest?.("[role=\"dialog\"]");
        const modalRoot = input.closest?.(".jetstream-modal, .profile-modal");
        const staticPreference = input.dataset.uiPickerStatic;
        const staticPicker = staticPreference === "true" || (staticPreference !== "false" && Boolean(dialogPanel));

        if (input._flatpickr) {
            if (isUiPickerDetached(input._flatpickr)) {
                destroyUiPicker(input);
            } else {
                syncUiPickerValue(input._flatpickr, input);
                syncUiPickerWidth(input._flatpickr, input);
                input.setAttribute("data-ui-picker-ready", "true");
                return;
            }
        }

        if (input._flatpickr) {
            input.setAttribute("data-ui-picker-ready", "true");
            return;
        }

        input.setAttribute("data-ui-picker-ready", "true");

        const config = {
            allowInput: false,
            clickOpens: true,
            disableMobile: true,
            static: staticPicker,
            onChange: (selectedDates, dateStr, instance) => {
                if (mode === "date-range") {
                    syncUiRangeTargets(input, selectedDates);
                    closeUiPickerAfterSelection(input, mode, selectedDates, dateStr, instance);

                    return;
                }

                dispatchUiPickerValue(input, dateStr);
                closeUiPickerAfterSelection(input, mode, selectedDates, dateStr, instance);
            },
            onValueUpdate: (selectedDates, dateStr, instance) => {
                if (mode === "date-range") {
                    syncUiRangeTargets(input, selectedDates);
                    closeUiPickerAfterSelection(input, mode, selectedDates, dateStr, instance);

                    return;
                }

                dispatchUiPickerValue(input, dateStr);
                closeUiPickerAfterSelection(input, mode, selectedDates, dateStr, instance);
            },
            onReady: (selectedDates, _dateStr, instance) => {
                enforceUiPickerOnlyInput(instance, input);
                syncUiPickerValue(instance, input);
                syncUiPickerWidth(instance, input);
                if (mode === "date-range" && selectedDates.length > 0) {
                    syncUiRangeTargets(input, selectedDates);
                }
                normalizeUiPickerCalendar(instance);
            },
            onOpen: (_selectedDates, _dateStr, instance) => {
                enforceUiPickerOnlyInput(instance, input);
                syncUiPickerValue(instance, input);
                syncUiPickerWidth(instance, input);
                normalizeUiPickerCalendar(instance);
            },
            onMonthChange: (_selectedDates, _dateStr, instance) => {
                normalizeUiPickerCalendar(instance);
            },
            onYearChange: (_selectedDates, _dateStr, instance) => {
                normalizeUiPickerCalendar(instance);
            },
        };

        const defaultDate = mode === "date-range"
            ? [
                uiPickerTargetValue(input.dataset.uiRangeFrom),
                uiPickerTargetValue(input.dataset.uiRangeTo),
            ].filter(Boolean)
            : uiPickerValue(input);

        if (Array.isArray(defaultDate) ? defaultDate.length > 0 : defaultDate) {
            config.defaultDate = defaultDate;
        }

        if (!staticPicker) {
            config.appendTo = modalRoot || document.body;
        }

        if (input.min) {
            config.minDate = input.min;
        }

        if (input.max) {
            config.maxDate = input.max;
        }

        if (mode === "time") {
            Object.assign(config, {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
            });
        } else if (mode === "datetime") {
            Object.assign(config, {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true,
            });
        } else if (mode === "date-range") {
            Object.assign(config, {
                mode: "range",
                dateFormat: "Y-m-d",
                conjunction: " - ",
            });
        } else {
            Object.assign(config, {
                dateFormat: "Y-m-d",
            });
        }

        const picker = window.flatpickr(input, config);
        enforceUiPickerOnlyInput(picker, input);
        syncUiPickerWidth(picker, input);
        normalizeUiPickerCalendar(picker);
    });
};

const scheduleUiPickerInit = (root = document) => {
    window.requestAnimationFrame(() => initUiPickers(root));
};

const openUiPicker = (input) => {
    if (!input) {
        return;
    }

    if (input._flatpickr && isUiPickerDetached(input._flatpickr)) {
        destroyUiPicker(input);
    }

    if (!input._flatpickr) {
        initUiPickers(input);
    }

    window.requestAnimationFrame(() => {
        if (input._flatpickr && isUiPickerDetached(input._flatpickr)) {
            destroyUiPicker(input);
            initUiPickers(input);
        }

        const picker = input._flatpickr;

        if (!picker || isUiPickerDetached(picker) || picker.input?.disabled || picker._input?.disabled) {
            return;
        }

        enforceUiPickerOnlyInput(picker, input);
        picker.open(undefined, picker.altInput || picker.input || input);
    });
};

const uiPickerInputFromTarget = (target) => {
    if (!target?.closest) {
        return null;
    }

    if (target.closest(".flatpickr-calendar")) {
        return null;
    }

    return target.closest("[data-ui-picker]")
        || target.closest(".user-native-field__control")?.querySelector?.("[data-ui-picker]")
        || null;
};

window.initUiPickers = initUiPickers;
window.scheduleUiPickerInit = scheduleUiPickerInit;

let uiPickerMountObserver = null;

const watchUiPickerMounts = () => {
    initUiPickers();

    if (!window.MutationObserver || uiPickerMountObserver) {
        return;
    }

    uiPickerMountObserver = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    initUiPickers(node);
                }
            });
        }
    });

    uiPickerMountObserver.observe(document.body, {
        childList: true,
        subtree: true,
    });
};

document.addEventListener("pointerdown", (event) => {
    const input = uiPickerInputFromTarget(event.target);

    if (!input) {
        return;
    }

    event.preventDefault();
    openUiPicker(input);
}, true);

document.addEventListener("keydown", (event) => {
    const input = uiPickerInputFromTarget(event.target);

    if (!input || !["Enter", " "].includes(event.key)) {
        return;
    }

    event.preventDefault();
    openUiPicker(input);
}, true);

["click", "focusin"].forEach((eventName) => {
    document.addEventListener(eventName, (event) => {
        const input = uiPickerInputFromTarget(event.target);

        if (input) {
            openUiPicker(input);
        }
    });
});

window.profilePhotoEditor = (config = {}) => ({
    currentPhotoUrl: config.initialPhotoUrl || null,
    defaultFileName: config.defaultFileName || "profile-photo.jpg",
    messages: {
        invalidFile: "Please choose a valid image file.",
        uploadFailed: "The photo could not be uploaded. Please try again.",
        processFailed: "The photo could not be processed. Please try another image.",
        ...(config.messages || {}),
    },
    cropModalOpen: false,
    uploadError: null,
    uploading: false,
    fileName: null,
    image: null,
    baseScale: 1,
    zoom: 1,
    offsetX: 0,
    offsetY: 0,
    dragging: false,
    dragPointerId: null,
    dragStartX: 0,
    dragStartY: 0,
    dragOriginX: 0,
    dragOriginY: 0,

    handleFileChange(event) {
        const [file] = event.target.files || [];

        if (!file) {
            return;
        }

        if (!file.type.startsWith("image/")) {
            this.uploadError = this.messages.invalidFile;
            this.$refs.photo.value = "";
            return;
        }

        this.fileName = file.name || this.defaultFileName;

        const reader = new FileReader();
        reader.onload = () => {
            const image = new Image();

            image.onload = () => {
                this.image = image;
                this.cropModalOpen = true;

                this.$nextTick(() => {
                    this.resetCropState();
                    this.renderCropCanvas();
                });
            };

            image.onerror = () => {
                this.uploadError = this.messages.processFailed;
                this.$refs.photo.value = "";
            };

            image.src = reader.result;
        };
        reader.onerror = () => {
            this.uploadError = this.messages.processFailed;
            this.$refs.photo.value = "";
        };
        reader.readAsDataURL(file);
    },

    resetCropState() {
        if (!this.image || !this.$refs.cropCanvas) {
            return;
        }

        const canvas = this.$refs.cropCanvas;
        this.baseScale = Math.max(canvas.width / this.image.width, canvas.height / this.image.height);
        this.zoom = 1.1;
        this.offsetX = 0;
        this.offsetY = 0;
        this.dragging = false;
    },

    closeCropModal() {
        if (this.uploading) {
            return;
        }

        this.cropModalOpen = false;
        this.dragging = false;
        this.$refs.photo.value = "";
    },

    startDrag(event) {
        if (!this.image) {
            return;
        }

        this.dragging = true;
        this.dragPointerId = event.pointerId;
        this.dragStartX = event.clientX;
        this.dragStartY = event.clientY;
        this.dragOriginX = this.offsetX;
        this.dragOriginY = this.offsetY;
        event.target.setPointerCapture?.(event.pointerId);
    },

    onDrag(event) {
        if (!this.dragging || this.dragPointerId !== event.pointerId) {
            return;
        }

        this.offsetX = this.dragOriginX + (event.clientX - this.dragStartX);
        this.offsetY = this.dragOriginY + (event.clientY - this.dragStartY);
        this.renderCropCanvas();
    },

    stopDrag() {
        this.dragging = false;
        this.dragPointerId = null;
    },

    clampOffsets() {
        if (!this.image || !this.$refs.cropCanvas) {
            return;
        }

        const canvas = this.$refs.cropCanvas;
        const scale = this.baseScale * this.zoom;
        const renderedWidth = this.image.width * scale;
        const renderedHeight = this.image.height * scale;
        const maxOffsetX = Math.max(0, (renderedWidth - canvas.width) / 2);
        const maxOffsetY = Math.max(0, (renderedHeight - canvas.height) / 2);

        this.offsetX = Math.min(maxOffsetX, Math.max(-maxOffsetX, this.offsetX));
        this.offsetY = Math.min(maxOffsetY, Math.max(-maxOffsetY, this.offsetY));
    },

    renderCropCanvas() {
        if (!this.image || !this.$refs.cropCanvas) {
            return;
        }

        this.clampOffsets();

        const canvas = this.$refs.cropCanvas;
        const context = canvas.getContext("2d");
        const scale = this.baseScale * this.zoom;
        const renderedWidth = this.image.width * scale;
        const renderedHeight = this.image.height * scale;
        const x = (canvas.width - renderedWidth) / 2 + this.offsetX;
        const y = (canvas.height - renderedHeight) / 2 + this.offsetY;

        context.clearRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = "#f4f8f2";
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.imageSmoothingQuality = "high";
        context.drawImage(this.image, x, y, renderedWidth, renderedHeight);
    },

    getCroppedBlob() {
        return new Promise((resolve, reject) => {
            const canvas = this.$refs.cropCanvas;

            if (!canvas) {
                reject(new Error("Canvas unavailable"));
                return;
            }

            canvas.toBlob((blob) => {
                if (blob) {
                    resolve(blob);
                    return;
                }

                reject(new Error("Unable to crop image"));
            }, "image/jpeg", 0.92);
        });
    },

    async saveCroppedPhoto() {
        if (!this.image || this.uploading) {
            return;
        }

        this.uploading = true;
        this.uploadError = null;

        try {
            const blob = await this.getCroppedBlob();
            const file = new File([blob], this.fileName || this.defaultFileName, {
                type: "image/jpeg",
            });

            this.$wire.upload("photo", file, () => {
                this.currentPhotoUrl = URL.createObjectURL(file);
                this.cropModalOpen = false;
                this.$refs.photo.value = "";
                this.uploading = false;
                this.$wire.updateProfileInformation();
            }, () => {
                this.uploadError = this.messages.uploadFailed;
                this.uploading = false;
            });
        } catch (error) {
            this.uploadError = this.messages.processFailed;
            this.uploading = false;
        }
    },
});

const resolveRuntimeAssetUrl = (path) => {
    if (!path) {
        return window.location.origin;
    }

    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    if (path.startsWith("/")) {
        return new URL(path, window.location.origin).toString();
    }

    return new URL(path, window.location.href).toString();
};

const OFFLINE_PAGE_PATH = "/offline.html";
const OFFLINE_RETURN_KEY = "app:offline:return-url";

const isOfflinePagePath = (pathname = window.location.pathname) =>
    pathname === OFFLINE_PAGE_PATH || pathname === "/offline";

const getCurrentRelativeUrl = () =>
    `${window.location.pathname}${window.location.search}${window.location.hash}`;

const normalizeRelativeUrl = (target) => {
    try {
        const url = new URL(target, window.location.origin);
        return `${url.pathname}${url.search}${url.hash}`;
    } catch (error) {
        return "/home";
    }
};

const buildOfflineRedirectUrl = (returnTo) => {
    const url = new URL(OFFLINE_PAGE_PATH, window.location.origin);

    if (returnTo) {
        url.searchParams.set("returnTo", normalizeRelativeUrl(returnTo));
    }

    return url.toString();
};

const storeOfflineReturnUrl = (target) => {
    const normalizedTarget = normalizeRelativeUrl(target);

    if (isOfflinePagePath(new URL(normalizedTarget, window.location.origin).pathname)) {
        return;
    }

    sessionStorage.setItem(OFFLINE_RETURN_KEY, normalizedTarget);
};

const redirectToOfflinePage = ({ force = false, returnTo = null } = {}) => {
    if (isOfflinePagePath()) {
        return;
    }

    if (!force && navigator.onLine) {
        return;
    }

    const target = returnTo || getCurrentRelativeUrl();
    storeOfflineReturnUrl(target);
    window.location.replace(buildOfflineRedirectUrl(target));
};

const restoreFromOfflinePage = () => {
    if (!isOfflinePagePath() || !navigator.onLine) {
        return;
    }

    const searchParams = new URLSearchParams(window.location.search);
    const returnTo =
        searchParams.get("returnTo") ||
        sessionStorage.getItem(OFFLINE_RETURN_KEY) ||
        "/home";

    sessionStorage.removeItem(OFFLINE_RETURN_KEY);
    window.location.replace(normalizeRelativeUrl(returnTo));
};

window.AppOffline = {
    redirectToOfflinePage,
    restoreFromOfflinePage,
};

document.addEventListener("livewire:navigated", () => {
    const isDark = localStorage.getItem("isDark") === "true";
    const systemDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    
    if (localStorage.getItem("isDark")) {
        if (isDark) {
            document.documentElement.classList.add("dark");
        } else {
            document.documentElement.classList.remove("dark");
        }
    } else {
        if (systemDark) {
            document.documentElement.classList.add("dark");
        } else {
            document.documentElement.classList.remove("dark");
        }
    }

    scheduleUiPickerInit();
});

document.addEventListener("livewire:init", () => {
    if (!window.Livewire?.hook) {
        return;
    }

    window.Livewire.hook("morph.added", ({ el }) => {
        if (el instanceof Element) {
            scheduleUiPickerInit(el);
        }
    });

    window.Livewire.hook("morph.updated", ({ el }) => {
        if (el instanceof Element && el.querySelector?.("[data-ui-picker]")) {
            scheduleUiPickerInit(el);
        }
    });
});

document.addEventListener("DOMContentLoaded", () => {

    watchUiPickerMounts();

    if (!navigator.onLine) {
        redirectToOfflinePage({ force: true });
        return;
    }

    restoreFromOfflinePage();
});

window.addEventListener("offline", () => {
    redirectToOfflinePage({ force: true });
});

window.addEventListener("online", () => {
    restoreFromOfflinePage();
});


document.addEventListener("click", (event) => {
    if (navigator.onLine || event.defaultPrevented) {
        return;
    }

    const link = event.target.closest("a[href]");

    if (!link || link.target === "_blank" || link.hasAttribute("download")) {
        return;
    }

    const nextUrl = new URL(link.href, window.location.origin);

    if (nextUrl.origin !== window.location.origin || isOfflinePagePath(nextUrl.pathname)) {
        return;
    }

    event.preventDefault();
    redirectToOfflinePage({
        force: true,
        returnTo: `${nextUrl.pathname}${nextUrl.search}${nextUrl.hash}`,
    });
});

document.addEventListener("submit", (event) => {
    if (navigator.onLine || event.defaultPrevented) {
        return;
    }

    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    event.preventDefault();
    redirectToOfflinePage({ force: true });
});

let map;

window.initializeMap = ({ onUpdate, location }) => {
    let defaultLocation = location ?? [-6.8905504, 109.3808162];
    map = L.map("map").setView(defaultLocation, 13);

    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 21,
    }).addTo(map);

    let marker = L.marker(defaultLocation, {
        draggable: true,
    }).addTo(map);

    marker.on("dragend", function (event) {
        let position = marker.getLatLng();
        updateCoordinates(position.lat, position.lng);
    });

    map.on("move", function () {
        let center = map.getCenter();
        marker.setLatLng(center);
        updateCoordinates(center.lat, center.lng);
    });

    updateCoordinates(defaultLocation[0], defaultLocation[1]);

    function updateCoordinates(lat, lng) {
        onUpdate(lat, lng);
    }
};

window.setMapLocation = ({ location }) => {
    if (location == null) return;

    map.setView(location, 13);
};

// The retained Laravel views use this optional capability probe for web fallbacks.
window.isNativeApp = () => false;
window.resolveRuntimeAssetUrl = resolveRuntimeAssetUrl;
window.openMap = (lat, lng) => {
    window.open(`https://www.google.com/maps?q=${lat},${lng}`, "_blank", "noopener,noreferrer");
};

// Mosallas-Group Pull-to-Refresh Logic Port
document.addEventListener('DOMContentLoaded', () => {
    const refreshContainer = document.querySelector(".refresh-container");
    const spinner = document.querySelector(".spinner");
    const hasRefreshUi = () =>
        !!(refreshContainer && spinner && refreshContainer.style && spinner.style);
    const isPullRefreshDisabled = () =>
        document.body?.classList.contains("scan-attendance-route") ||
        document.body?.classList.contains("is-native-scanning") ||
        Boolean(document.querySelector(".scan-attendance-page"));
    
    // We don't strictly need 'main' for Overlay style if we aren't moving it
    // But we might want to check if it exists purely for safety
    if (!hasRefreshUi()) return;

    let isLoading = false;
    let pStartY = 0;
    
    // Threshold to trigger refresh
    const THRESHOLD = 80;

    const loadInit = () => {
        if (!hasRefreshUi() || isPullRefreshDisabled()) return;
        refreshContainer.classList.add("load-init");
        isLoading = true;
    };

    const swipeStart = (e) => {
        if (isLoading || isPullRefreshDisabled()) {
            pStartY = 0;
            return;
        }
        
        // Only track if we are at the top (or very close)
        // Using 2px tolerance for safe measure
        if (window.scrollY > 2) return;

        pStartY = e.touches[0].screenY;
        if (!hasRefreshUi()) return;
        
        // Remove transitions during drag
        refreshContainer.style.transition = 'none';
        spinner.style.transition = 'none';
    };

    const swipe = (e) => {
        if (isPullRefreshDisabled()) {
            pStartY = 0;
            resetLayout();
            return;
        }

        if (isLoading || pStartY === 0) return;

        const touch = e.touches[0];
        const currentY = touch.screenY;
        const diff = currentY - pStartY;

        // Only handle Pull Down when at Top
        if (diff > 0 && window.scrollY <= 2) {
            if (!hasRefreshUi()) return;
            // Prevent native scroll/overscroll behavior
            if (e.cancelable) e.preventDefault();

            // Resistance Logic
            // Formula: (diff * 0.5) to feel "heavy"
            const pullDistance = diff * 0.5;

            // Cap the visual pull distance
            if (pullDistance <= 250) {
                // Determine Offset:
                // Start: -50px (hidden)
                // Pull 0px -> -50px
                // Pull 100px -> 50px visual
                const marginTop = pullDistance - 50;
                
                refreshContainer.style.marginTop = `${marginTop}px`;
                spinner.style.transform = `rotate(${pullDistance * 2.5}deg)`;
            }
        }
    };

    const swipeEnd = (e) => {
        if (isPullRefreshDisabled()) {
            pStartY = 0;
            resetLayout();
            return;
        }

        if (isLoading || pStartY === 0) {
            pStartY = 0; // Reset
            return;
        }

        const touch = e.changedTouches[0];
        const currentY = touch.screenY;
        const diff = currentY - pStartY;
        const pullDistance = diff * 0.5;
        if (!hasRefreshUi()) {
            pStartY = 0;
            return;
        }

        // Restore Transitions
        refreshContainer.style.transition = 'margin-top 0.3s cubic-bezier(0.25, 1, 0.5, 1)';
        spinner.style.transition = 'transform 0.3s linear';

        pStartY = 0; // Reset start marker

        // Trigger?
        if (pullDistance >= THRESHOLD && window.scrollY <= 2) {
            // YES - Refresh
            loadInit();
            
            // Snap to "Active" position
            // e.g. 20px from top
            refreshContainer.style.marginTop = "15px"; 
            refreshContainer.classList.add("load-start");
            
            // Reload after delay
            setTimeout(() => {
                window.location.reload();
            }, 800);
            
            // Safety timeout
            setTimeout(() => {
                resetLayout();
            }, 5000);

        } else {
            // NO - Reset
            resetLayout();
        }
    };

    const resetLayout = () => {
        if (!hasRefreshUi()) return;
        isLoading = false;
        refreshContainer.classList.remove("load-init");
        refreshContainer.classList.remove("load-start");
        refreshContainer.style.marginTop = "-50px";
        spinner.style.transform = "rotate(0deg)";
    };
    
    // Use non-passive listener to allow preventDefault()
    document.addEventListener("touchstart", swipeStart, { passive: true });
    // IMPORTANT: passive: false is required to block native scroll
    document.addEventListener("touchmove", swipe, { passive: false });
    document.addEventListener("touchend", swipeEnd, { passive: true });
});


document.addEventListener("livewire:navigating", () => {
    const root = document.querySelector("[data-face-enrollment-root]");

    if (!root || !window.Alpine?.$data) {
        return;
    }

    const component = window.Alpine.$data(root);

    if (component?.cleanup) {
        component.cleanup();
    }
});


// Livewire Navigation (SPA-style links)
document.addEventListener('livewire:navigating', () => {

    if (!navigator.onLine) {
        redirectToOfflinePage({ force: true });
    }
});
