"use strict";
Plushie.checkForNamespace(Plushie.Plushmancer, "Listbox").Option = class Option extends Plushie.EventTarget {
	constructor(text) {
		super();
		Plushie.Utils.definePrivateProperties(this, "checkbox", "label", "listItem", "option", {
			static: { value: Option },
			text: { enumerable: true, value: text }
		})
	}

	get checkbox() { return Plushie.Utils.getPrivateProperty(this, "checkbox", () => Plushie.Utils.createElement("input", { ["aria-disabled"]: true, tabindex: -1, type: "checkbox" })); }

	get label() {
		return Plushie.Utils.getPrivateProperty(this, "label", () => {
			const label = window.document.createElement("label");
			label.appendChild(this.checkbox);
			Plushie.Utils.createTextNode(this.text, label);
			return label;
		});
	}

	get listItem() {
		return Plushie.Utils.getPrivateProperty(this, "listItem", () => {
			const listItem = Plushie.Utils.createElement("li", { ["aria-checked"]: false, ["aria-selected"]: false, role: "option", title: this.text });
			listItem.appendChild(this.label);
			listItem.addEventListener("click", (event) => this.onClick(event));
			listItem.addEventListener("mouseover", (event) => this.onMouseOver(event));
			return listItem;
		});
	}

	get option() { return Plushie.Utils.getPrivateProperty(this, "option", () => Plushie.Utils.createElement("option", {}, undefined, this.text)); }

	get isHighlighted() { return this.listItem.getAttribute("aria-current") === "true"; }
	get isSelected() { return this.option.selected; }

	set isHighlighted(isHighlighted) {
		isHighlighted = Plushie.Utils.toBoolean(isHighlighted);

		if (isHighlighted)
			this.listItem.setAttribute("aria-current", isHighlighted);
		else
			this.listItem.removeAttribute("aria-current");
	}

	set isSelected(isSelected) {
		isSelected = Plushie.Utils.toBoolean(isSelected);

		if (this.isSelected !== isSelected) {
			this.option.selected = isSelected;
			this.listItem.setAttribute("aria-checked", isSelected);
			this.listItem.setAttribute("aria-selected", isSelected);
			Plushie.Utils.setImmediate(() => this.checkbox.checked = this.isSelected);
		}
	}

	dispatchEvent(name) { super.dispatchEvent(new window.CustomEvent(name.toString(), { detail: { option: this } })); }

	highlight() {
		this.isHighlighted = true;
		this.dispatchEvent("highlight");
	}

	onClick(event) {
		this.select();
		event.preventDefault();
		event.stopPropagation();
	}

	onMouseOver(event) {
		this.highlight();
		event.preventDefault();
		event.stopPropagation();
	}

	select(isSelected = undefined) {
		this.isSelected = (isSelected === undefined) ? !this.isSelected : Plushie.Utils.toBoolean(isSelected);
		this.dispatchEvent("select");
	}
}
window.Object.defineProperties(Plushie.Plushmancer.Listbox, { Option: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("Plushmancer/Listbox/Option");