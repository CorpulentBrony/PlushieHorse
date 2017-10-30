"use strict";
Plushie.checkForNamespace(Plushie.Plushmancer, "Sorter").GroupCollection = class GroupCollection extends Plushie.ArrayEventTarget {
	constructor(size, sortableColumnsList, filterRow) {
		super(size | 0);

		if (!(sortableColumnsList instanceof window.Set && filterRow instanceof window.HTMLTableRowElement))
			return;
		window.Object.defineProperties(this, { static: { value: GroupCollection } });
		super.fill(undefined).forEach((group, index) => {
			if (sortableColumnsList.has(index)) {
				this[index] = new this.static.parent.Group();
				filterRow.cells.item(index).appendChild(this[index].value);
				this[index].addEventListener("sort", (event) => this.onSort(event, index));
			} else
				this[index] = false;
		});
	}

	clearSelected(exceptIndex = undefined) {
		if (exceptIndex !== undefined)
			exceptIndex |= 0;
		super.forEach((group, index) => {
			if (group instanceof this.static.parent.Group && (exceptIndex === undefined || index !== exceptIndex))
				group.direction = 0;
		});
	}

	onSort(event, index) {
		if (!(event instanceof window.Event))
			return;
		index |= 0;
		this.clearSelected(index);
		super.dispatchEvent(new window.CustomEvent("sort", { detail: { direction: event.detail.direction, index, sorter: event.detail.sorter } }));
	}
}
window.Object.defineProperties(Plushie.Plushmancer.Sorter.GroupCollection, { parent: { value: Plushie.Plushmancer.Sorter } });
window.Object.defineProperties(Plushie.Plushmancer.Sorter, { GroupCollection: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("Plushmancer/Sorter/GroupCollection");