"use strict";
Plushie.checkForNamespace(Plushie.Plushmancer, "Listbox").OptionCollection = class OptionCollection extends Plushie.ArrayEventTarget {
	constructor(values) {
		const isArray = window.Array.isArray(values);
		super(isArray ? values.size : 0);

		if (!isArray)
			return;
		Plushie.Utils.definePrivateProperties(this, "list", "selectElement", {
			_highlightedIndex: { value: -1, writable: true },
			_lastSelected: { value: -1, writable: true },
			values: { enumerable: true, value: window.Array.from(values) },
			static: { value: OptionCollection }
		});
		this.values.forEach((option, index) => {
			this[index] = new this.static.parent.Option(option);
			this.list.appendChild(this[index].listItem);
			this.selectElement.appendChild(this[index].option);
			this[index].addEventListener("highlight", (event) => this.onHighlight(event, index));
			this[index].addEventListener("select", (event) => this.onSelect(event, index));
		});
	}

	get highlighted() { return this[this.highlightedIndex]; }
	get highlightedIndex() { return this._highlightedIndex; }
	get lastSelected() { return this._lastSelected; }
	get list() { return Plushie.Utils.getPrivateProperty(this, "list", () => Plushie.Utils.createElement("menu", { role: "presentation" })); }
	get names() { return super.map((option) => option.text); }
	get selectElement() { return Plushie.Utils.getPrivateProperty(this, "selectElement", () => Plushie.Utils.createElement("select", { ["aria-hidden"]: true, class: "invisible", multiple: true, tabindex: -1 })); }
	get selected() { return super.filter((option) => option.isSelected); }

	set highlightedIndex(highlightedIndex) {
		highlightedIndex |= 0;
		this._highlightedIndex = (highlightedIndex >= this.length) ? -1 : (highlightedIndex < -1) ? this.length - 1 : highlightedIndex;
	}

	set lastSelected(lastSelected) { this._lastSelected = window.Math.min(window.Math.max(lastSelected | 0, 0), this.length - 1); }

	clearHighlighted(exceptIndex = undefined) {
		const hasException = exceptIndex !== undefined;
		exceptIndex |= 0;
		super.forEach((option, index) => {
			if (!hasException || index !== exceptIndex)
				option.isHighlighted = false;
		});
		this.highlightedIndex = hasException ? exceptIndex : -1;
	}

	clearSelected() {
		super.forEach((option) => option.isSelected = false);
		this.dispatchEvent("select");
	}

	dispatchEvent(name, option = undefined, index = undefined) { super.dispatchEvent(new window.CustomEvent(name.toString(), { detail: { option, index } })); }

	hasCheckbox(checkbox) {
		if (!(checkbox instanceof window.HTMLInputElement))
			return false;
		return super.some((option) => window.Object.is(option.checkbox, checkbox));
	}

	highlight(index) {
		index |= 0;
		this[(index >= this.length) ? 0 : (index < 0) ? this.length - 1 : index].highlight();
	}

	highlightTypeAhead(typeAhead) {
		const index = super.findIndex((option) => option.text.toLowerCase().startsWith(typeAhead.toLowerCase()));

		if (index >= 0)
			this.highlight(index);
	}

	onHighlight(event, index) {
		if (!(event instanceof window.CustomEvent))
			return;
		index |= 0;
		this.clearHighlighted(index);
		this.dispatchEvent("highlight", event.detail.option, index);
	}

	onSelect(event, index) {
		if (!(event instanceof window.CustomEvent))
			return;
		else if (event.detail.option.isSelected)
			this.lastSelected = index;
		this.dispatchEvent("select", event.detail.option, index);
	}

	select(fromIndex, toIndex, isSelected = undefined, selectAllFirst = false) {
		[fromIndex, toIndex] = Array.of(fromIndex, toIndex).map((index) => window.Math.min(window.Math.max(index | 0, 0), this.length - 1)).sort((a, b) => a - b);
		const slice = super.slice(fromIndex, toIndex + 1);

		if (Plushie.Utils.toBoolean(selectAllFirst) && !slice.every((option) => option.isSelected))
			slice.forEach((option) => option.select(true));
		else
			slice.forEach((option) => option.select(isSelected));
		this.lastSelected = this.highlightedIndex;
	}
}
window.Object.defineProperties(Plushie.Plushmancer.Listbox.OptionCollection, { parent: { value: Plushie.Plushmancer.Listbox } });
window.Object.defineProperties(Plushie.Plushmancer.Listbox, { OptionCollection: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("Plushmancer/Listbox/OptionCollection");