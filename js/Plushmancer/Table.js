"use strict";
Plushie.addClassToNamespace(Plushie.Plushmancer, "Table", class Table {
	constructor() {
		Plushie.Utils.definePrivateProperties(
			this, "_element", "definedListboxes", "columns", "filteredColumnsList", "filterRowElement", "listboxes", "relevantColumnsList", "rows", "sortableColumnsList", "sorters", "sourceColumnCount", "tbody", "thead", {
				announcer: { enumerable: true, value: Plushie.Utils.createElement("output", { ["aria-live"]: "polite", class: "invisible", for: Plushie.Plushmancer.Table.FILTER_ROW_ID, role: "log" }) }
			}
		);
		this._element.parentElement.insertBefore(this.announcer, this._element.nextSibling);
	}

	get _element() { return Plushie.Utils.getPrivateProperty(this, "_element", () => window.document.getElementById(Plushie.Plushmancer.Table.LIST_ID)); }
	get _undefinedColumnList() { return window.Array.prototype.fill.call(new window.Array(this.sourceColumnCount), undefined); }
	get columns() { return Plushie.Utils.getPrivateProperty(this, "columns", () => new Table.ColumnCollection(this)); }
	get definedListboxes() { return Plushie.Utils.getPrivateProperty(this, "definedListboxes", () => this.listboxes.filter((listbox) => listbox instanceof Plushie.Plushmancer.Listbox)); }
	get filteredColumnsList() { return Plushie.Utils.getPrivateProperty(this, "filteredColumnsList", () => this.getColumnListByDatasetProperty("filter")); }

	get filterRowElement() { 
		return Plushie.Utils.getPrivateProperty(this, "filterRowElement", () => {
			const filterRowElement = window.document.getElementById(Table.FILTER_ROW_ID);
			filterRowElement.setAttribute("role", "radiogroup row");
			return filterRowElement;
		});
	}

	get headerRow() { return this.rows.header; }
	get listboxes() { return Plushie.Utils.getPrivateProperty(this, "listboxes", () => new Plushie.Plushmancer.Listbox.Collection(this.columns, this.filteredColumnsList, this.filterRowElement)); }
	get relevantColumnsList() { return Plushie.Utils.getPrivateProperty(this, "relevantColumnsList", () => Plushie.Utils.mergeSets(this.filteredColumnsList, this.sortableColumnsList)); }
	get rows() { return Plushie.Utils.getPrivateProperty(this, "rows", () => new Table.RowCollection(this)); }
	get sortableColumnsList() { return Plushie.Utils.getPrivateProperty(this, "sortableColumnsList", () => this.getColumnListByDatasetProperty("sort")); }
	get sorters() { return Plushie.Utils.getPrivateProperty(this, "sorters", () => new Plushie.Plushmancer.Sorter.GroupCollection(this.sourceColumnCount, this.sortableColumnsList, this.filterRowElement)); }

	get sourceColumnCount() { return Plushie.Utils.getPrivateProperty(this, "sourceColumnCount", () => (this._element.rows.length > 0) ? this._element.rows.item(0).cells.length : 0) | 0; }

	get tbody() {
		return Plushie.Utils.getPrivateProperty(this, "tbody", () => {
			this._tbody = false;

			for (const row of this._element.rows)
				if (row.id === Table.FILTER_ROW_ID || !window.Array.prototype.every.call(row.cells, (column) => column.tagName === "TD"))
					this.thead.appendChild(row);
				else
					break;
			const tbody = this._element.tBodies.item(0);
			tbody.setAttribute("role", "rowgroup");
			return tbody;
		});
	}

	get thead() {
		if (this._thead !== undefined || this._tbody === false)
			return Plushie.Utils.getPrivateProperty(this, "thead", () => this._element.insertBefore(Plushie.Utils.createElement("thead", { role: "rowgroup" }), this._element.tBodies.item(0)));
		return this.tsections.thead;
	}

	get tsections() { return { tbody: this.tbody, thead: this.thead }; }

	set announcement(message) {
		const announcement = Plushie.Utils.createElement("div", {}, this.announcer, message.toString());
		window.setTimeout(() => this.announcer.removeChild(announcement), 2000);
	}

	addMenus() {
		this.listboxes.addEventListener("select", (event) => this.onSelect(event));
		delete this.addMenus;
		return this;
	}

	addSorters() {
		this.sorters.addEventListener("sort", (event) => this.onSort(event));
		delete this.addSorters;
		return this;
	}

	getColumnListByDatasetProperty(property) { return window.Array.prototype.reduce.call(this.headerRow.cells, (list, cell, column) => (typeof cell.dataset[property.toString()] === "string") ? list.add(column) : list, new window.Set()); }

	init() {
		this._element.setAttribute("aria-busy", true);
		this.addMenus().addSorters();
		this._element.removeAttribute("aria-busy");
		return this;
	}

	onSelect(event) {
		if (!(event instanceof window.CustomEvent))
			return;
		this._element.setAttribute("aria-busy", true);
		const allowedValues = event.detail.listbox.options.selected.names;
		this.rows.forEach((row) => row.filter(event.detail.columnIndex, allowedValues));
		this.announcement = `The filter for column ${(allowedValues.length === 0) ? `${this.columns[event.detail.columnIndex].name} has been removed` : `${this.columns[event.detail.columnIndex].name} is set to show ${allowedValues.join(", ")}`}.`;
		this._element.removeAttribute("aria-busy");
	}

	onSort(event) {
		if (!(event instanceof window.CustomEvent))
			return;
		this._element.setAttribute("aria-busy", true);
		this.columns.sort(event.detail.index, Plushie.Plushmancer.Sorter.Direction[event.detail.direction]);
		this.rows.sort(event.detail.index, event.detail.direction);

		if (event.detail.direction === 0)
			this.announcement = "The table has reverted to its original sort order.";
		else
			this.announcement = `The table is now sorted ${Plushie.Plushmancer.Sorter.Direction[event.detail.direction].toString().toLowerCase()} by the values in column ${this.columns[event.detail.index].name}`;
		this._element.removeAttribute("aria-busy");
	}
});
window.Object.defineProperties(Plushie.Plushmancer.Table, { 
	LIST_ID: { enumerable: true, get: () => "PlushmancerListOutput" },
	FILTER_ROW_ID: { enumerable: true, get: () => `${Plushie.Plushmancer.Table.LIST_ID}FilterRow` },
	_getNumericDataset: { configurable: false, enumerable: false, writable: false }
});
window.Object.defineProperties(Plushie.Plushmancer, { Table: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("Plushmancer/Table");