"use strict";
Plushie.checkForNamespace(Plushie.Plushmancer, "Listbox").Collection = class Collection extends Plushie.ArrayEventTarget {
	constructor(columns, filteredColumnsList, filterRow) {
		const columnsIsArray = window.Array.isArray(columns);
		super(columnsIsArray ? columns.length : 0);

		if (!(columnsIsArray && filteredColumnsList instanceof window.Set && filterRow instanceof window.HTMLTableRowElement))
			return;
		Plushie.Utils.definePrivateProperties(this, "defined", { static: { value: Collection } });
		const collection = window.document.createDocumentFragment();
		const td = Plushie.Utils.createElement("td", { class: "plushmancer-filter-widget" });
		super.fill(undefined).forEach((listbox, index) => {
			if (filteredColumnsList.has(index)) {
				const thisTd = td.cloneNode();
				this[index] = new Plushie.Plushmancer.Listbox(columns[index].uniqueValues);
				thisTd.appendChild(this[index].options.selectElement);
				thisTd.appendChild(this[index].listbox);
				// thisTd.appendChild(this[index].dropdown);
				collection.appendChild(thisTd);
				this[index].addEventListener("focus", (event) => this.onFocus(event, index));
				this[index].addEventListener("focusout", (event) => this.onFocusOut(event, index));
				this[index].addEventListener("highlight", (event) => this.onHighlight(event, index));
				this[index].addEventListener("open", (event) => this.onOpen(event, index));
				this[index].addEventListener("select", (event) => this.onSelect(event, index));
				this[index].width = columns[index].width;
			} else {
				this[index] = false;
				collection.appendChild(td.cloneNode());
			}
		});
		filterRow.appendChild(collection);
		this.defined.forEach((listbox) => listbox.options.clearSelected());
	}

	get defined() { return Plushie.Utils.getPrivateProperty(this, "defined", () => super.filter((listbox) => listbox instanceof Plushie.Plushmancer.Listbox)); }

	onFocus(event, columnIndex) {
		this.defined.forEach((listbox) => {
			if (!window.Object.is(listbox, event.detail.listbox))
				listbox.focusOut();
		});
		this.passEvent("focus", event, columnIndex);
	}

	onFocusOut(event, columnIndex) { this.passEvent("focusout", event, columnIndex); }
	onHighlight(event, columnIndex) { this.passEvent("highlight", event, columnIndex); }
	onOpen(event, columnIndex) { this.passEvent("open", event, columnIndex); }
	onSelect(event, columnIndex) { this.passEvent("select", event, columnIndex); }

	passEvent(name, event, columnIndex) {
		if (!(event instanceof window.CustomEvent))
			return;
		super.dispatchEvent(new window.CustomEvent(name.toString(), { detail: window.Object.assign({ columnIndex }, event.detail) }));
	}
}
window.Object.defineProperties(Plushie.Plushmancer.Listbox.Collection, { parent: { value: Plushie.Plushmancer.Listbox } });
window.Object.defineProperties(Plushie.Plushmancer.Listbox, { Collection: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("Plushmancer/Listbox/Collection");