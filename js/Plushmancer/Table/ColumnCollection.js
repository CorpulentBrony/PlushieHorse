"use strict";
Plushie.checkForNamespace(Plushie.Plushmancer, "Table").ColumnCollection = class ColumnCollection extends window.Array {
	constructor(table) {
		super((table instanceof Plushie.Plushmancer.Table) ? table.headerRow.cells.length : 0);

		if (!(table instanceof Plushie.Plushmancer.Table))
			return;
		table.rows.reduce((result, row) => {
			row.forEach((column, index) => {
				if (!table.relevantColumnsList.has(index))
					return;
				else if (window.Array.isArray(result[index]))
					result[index].push(column);
				else
					result[index] = Plushie.Plushmancer.Table.Column.of(column);
				result[index].header = table.headerRow.cells.item(index);
			});
			return result;
		}, this);
	}

	sort(column, directionDescription) {
		column |= 0;
		super.forEach((column) => column.header.removeAttribute("aria-sort"));

		if (directionDescription !== undefined)
			this[column].header.setAttribute("aria-sort", directionDescription.toString());
	}
}
window.Object.defineProperties(Plushie.Plushmancer.Table, { ColumnCollection: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("Plushmancer/Table/ColumnCollection");