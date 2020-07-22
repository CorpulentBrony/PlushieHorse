"use strict";
Plushie.checkForNamespace(Plushie.Plushmancer, "Sorter").Group = class Group extends Plushie.EventTarget {
	constructor() {
		super();
		Plushie.Utils.definePrivateProperties(this, "ascending", "descending", "group", { static: { value: Group } });
	}

	get ascending() { return Plushie.Utils.getPrivateProperty(this, "ascending", () => new this.static.parent(this.static.parent.Direction.Ascending)); }
	get descending() { return Plushie.Utils.getPrivateProperty(this, "descending", () => new this.static.parent(this.static.parent.Direction.Descending)); }
	get direction() { return this.ascending.isSelected ? this.static.parent.Direction.Ascending : this.descending.isSelected ? this.static.parent.Direction.Descending : 0; }

	get group() {
		return Plushie.Utils.getPrivateProperty(this, "group", () => {
			const group = Plushie.Utils.createElement("span", { class: "plushmancer-sorters" });
			this.ascending.addEventListener("sort", (event) => this.onSort(event));
			this.descending.addEventListener("sort", (event) => this.onSort(event));
			group.appendChild(this.ascending.value);
			group.appendChild(this.descending.value);
			return group;
		});
	}

	get value() { return this.group; }

	set direction(direction) {
		this.ascending.isSelected = this.descending.isSelected = false;
		direction = this.static.parent.Direction.toNumber(direction);

		if (direction === this.static.parent.Direction.Ascending)
			this.ascending.isSelected = true;
		else if (direction === this.static.parent.Direction.Descending)
			this.descending.isSelected = true;
	}

	onSort(event) {
		if (!(event instanceof window.Event))
			return;

		if (Object.is(event.detail.sorter, this.ascending))
			this.descending.isSelected = false;
		else
			this.ascending.isSelected = false;
		super.dispatchEvent(new window.CustomEvent("sort", { detail: { direction: event.detail.direction, group: this, sorter: event.detail.sorter } }));
	}
}
window.Object.defineProperties(Plushie.Plushmancer.Sorter.Group, { parent: { value: Plushie.Plushmancer.Sorter } });
window.Object.defineProperties(Plushie.Plushmancer.Sorter, { Group: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("Plushmancer/Sorter/Group");