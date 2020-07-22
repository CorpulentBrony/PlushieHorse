"use strict";
Plushie.checkForNamespace(Plushie.Plushmancer, "Table").RowCollection = class RowCollection extends window.Array {
	constructor(table) {
		super((table instanceof Plushie.Plushmancer.Table) ? table.tbody.rows.length : 0);

		if (!(table instanceof Plushie.Plushmancer.Table))
			return;
		window.Array.prototype.reduce.call(table.tbody.rows, (result, row, index) => {
			result[index] = Plushie.Plushmancer.Table.Row.from(row, window.Array.prototype.map.call(
				row.cells, (column) => new Plushie.Plushmancer.Table.Element((column.innerText.search(/, /) > -1) ? column.innerText.split(", ").filter((item) => item.length > 0) : column.innerText)
			));
			return result;
		}, this);
		window.Object.defineProperties(this, {
			_unsorted: { enumerable: true, value: undefined, writable: true },
			header: { enumerable: true, value: self.Array.prototype.find.call(table.thead.rows, (row) => row.id !== Plushie.Plushmancer.Table.FILTER_ROW_ID) }
		});
	}

	sort(column, direction) {
		[column, direction] = [column | 0, direction | 0];

		if (direction === 0) {
			if (window.Array.isArray(this._unsorted))
				super.splice(0, this.length, ...this._unsorted);
			else
				return;
		} else {
			if (this._unsorted === undefined)
				window.Object.defineProperties(this, { _unsorted: { value: super.slice(0), writable: false } });
			super.sort((rowA, rowB) => direction * (rowA[column].toNumber() - rowB[column].toNumber()))
		}
		super.forEach((row) => row.element.parentNode.appendChild(row.element));
	}
}
window.Object.defineProperties(Plushie.Plushmancer.Table, { RowCollection: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("Plushmancer/Table/RowCollection");