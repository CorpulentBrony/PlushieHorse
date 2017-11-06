"use strict";
Plushie.addClassToNamespace(Plushie.Plushmancer, "Listbox", class Listbox extends Plushie.EventTarget {
	constructor(values) {
		super();
		if (!window.Array.isArray(values))
			return;
		Plushie.Utils.definePrivateProperties(this, "dropdown", "id", "listbox", "options", "output", "typeAheadBuffer", {
			static: { value: Listbox },
			values: { enumerable: true, value: values }
		});
	}

	get dropdown() { 
		return Plushie.Utils.getPrivateProperty(this, "dropdown", () => {
			const dropdown = Plushie.Utils.createElement("div", { ["aria-hidden"]: true, id: this.id, class: "plushmancer-dropdown" });
			dropdown.appendChild(this.options.list);
			return dropdown;
		});
	}

	get id() { return Plushie.Utils.getPrivateProperty(this, "id", () => Plushie.Utils.generateRandomId("dropdown")); }
	get isFocused() { return this.listbox.classList.contains("plushmancer-active"); }
	get isOpen() { return this.dropdown.getAttribute("aria-hidden") !== "true"; }
	get outputText() { return this.output.title; }

	get listbox() {
		return Plushie.Utils.getPrivateProperty(this, "listbox", () => {
			const listbox = Plushie.Utils.createElement("div", { ["aria-controls"]: "PlushmancerListOutput", ["aria-multiselectable"]: true, role: "listbox", tabindex: 0 });
			listbox.appendChild(this.output);
			listbox.appendChild(this.dropdown);
			listbox.addEventListener("click", () => this.open());
			listbox.addEventListener("focus", (event) => this.focus(event));
			listbox.addEventListener("focusout", (event) => this.focusOut(event));
			listbox.addEventListener("keydown", (event) => this.onKeyDown(event));
			return listbox;
		});
	}

	get options() {
		return Plushie.Utils.getPrivateProperty(this, "options", () => {
			const options = new Listbox.OptionCollection(this.values);
			options.addEventListener("highlight", (event) => this.onHighlight(event));
			options.addEventListener("select", (event) => this.onSelect(event));
			return options;
		});
	}

	get output() { return Plushie.Utils.getPrivateProperty(this, "output", () => Plushie.Utils.createElement("output", { ["aria-haspopup"]: true, ["aria-live"]: "polite", for: this.id })); }
	get typeAheadBuffer() { return Plushie.Utils.getPrivateProperty(this, "typeAheadBuffer", () => new Plushie.TypeAheadBuffer()); }
	get value() { return this.listbox; }
	get width() { return this.listbox.offsetWidth; }

	set isFocused(isFocused) {
		if (Plushie.Utils.toBoolean(isFocused))
			this.listbox.classList.add("plushmancer-active");
		else
			this.listbox.classList.remove("plushmancer-active");
	}

	set isOpen(isOpen) {
		isOpen = Plushie.Utils.toBoolean(isOpen);

		if (!isOpen)
			this.options.clearHighlighted();
		this.dropdown.setAttribute("aria-hidden", !isOpen);
		this.typeAheadBuffer.reset();
	}

	set outputText(outputText) { this.output.textContent = this.output.title = outputText.toString(); }
	set width(width) { this.listbox.style.width = `${(width | 0).toString()}px`; }

	dispatchEvent(name, detail = {}) { super.dispatchEvent(new window.CustomEvent(name.toString(), { detail: window.Object.assign({ listbox: this }, detail) })); }

	focus() {
		if (this.isFocused)
			return;
		this.isFocused = true;
		this.dispatchEvent("focus");
	}

	focusOut(event = undefined) {
		if (!this.isFocused || (event instanceof window.FocusEvent && (event.relatedTarget == this.listbox || this.options.hasCheckbox(event.relatedTarget))))
			return;
		this.isOpen = this.isFocused = false;
		this.options.clearHighlighted();
		this.dispatchEvent("focusout");
	}

	onHighlight(event) {
		if (!(event instanceof window.CustomEvent))
			return;
		if (this.options.highlightedIndex >= 0 && !this.isOpen)
			this.open();
		this.dispatchEvent("highlight", event.detail);
	}

	onKeyDown(event) {
		if (!(event instanceof window.KeyboardEvent))
			return;
		const key = (typeof event.key === "string") ? event.key : Plushie.KEY_CODES.get(event.keyCode);

		switch (key) {
			case " ":
			case "Accept":
			case "Enter":
			case "MediaPlay":
			case "Select":
			case "Spacebar":
				if (this.options.highlightedIndex === -1)
					this.open();
				else
					if (event.shiftKey && this.options.lastSelected !== -1)
						this.options.select(this.options.highlightedIndex, this.options.lastSelected, true);
					else
						this.options.highlighted.select();
				break;
			case "ArrowDown":
			case "ChannelDown":
			case "Down":
				this.options.highlight((this.options.highlightedIndex >= this.options.length) ? 0 : this.options.highlightedIndex + 1);

				if (event.shiftKey)
					this.options.highlighted.select();
				break;
			case "ArrowUp":
			case "ChannelUp":
			case "Up":
				this.options.highlight(((this.options.highlightedIndex <= 0) ? this.options.length : this.options.highlightedIndex) - 1);

				if (event.shiftKey)
					this.options.highlighted.select();
				break;
			case "Cancel":
			case "Escape":
			case "Esc":
			case "MediaStop":
				this.isOpen = false; break;
			case "End":
				if (!this.isOpen)
					return;
				else {
					const highlightedIndex = this.options.highlightedIndex;
					this.options.highlight(this.options.length - 1);

					if (event.ctrlKey && event.shiftKey)
						this.options.select(highlightedIndex, this.options.length - 1, true);
				}
				break;
			case "Home":
				if (!this.isOpen)
					return;
				else {
					const highlightedIndex = this.options.highlightedIndex;
					this.options.highlight(0);

					if (event.ctrlKey && event.shiftKey)
						this.options.select(0, highlightedIndex, true);
				}
				break;
			case "Tab": return;
			case "A":
			case "a":
				if (this.isOpen && event.ctrlKey) {
					this.options.highlight(0);
					this.options.select(0, this.options.length - 1, undefined, true);
					break;
				}
			default:
				if (!this.isOpen)
					return;
				else if (key.length === 1) {
					const charCode = key.toLowerCase().charCodeAt(0);

					if (charCode >= 97 && charCode <= 122) {
						this.options.highlightTypeAhead(this.typeAheadBuffer.add(key).toString());
						break;
					}
				}
				return;
		}
		event.preventDefault();
	}

	onSelect(event) {
		if (!(event instanceof window.CustomEvent))
			return;
		const selected = this.options.selected;
		this.outputText = (selected.length > 0) ? `Filters: ${selected.names.join(", ")}` : "Filter";
		this.dispatchEvent("select", event.detail);
	}

	open() {
		this.isOpen = !this.isOpen;

		if (this.isOpen) {
			this.options.highlight(0);
			this.dispatchEvent("open");
		}
	}
});
window.Object.defineProperties(Plushie.Plushmancer, { Listbox: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("Plushmancer/Listbox");