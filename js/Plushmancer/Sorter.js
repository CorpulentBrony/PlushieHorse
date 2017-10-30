"use strict";
Plushie.addClassToNamespace(Plushie.Plushmancer, "Sorter", class Sorter extends Plushie.EventTarget {
	static get Arrow() {
		return Plushie.Utils.getPrivateProperty(this, "Arrow", () => {
			const Arrow = window.Object.create(window.Object.prototype);
			Arrow[Arrow["▲"] = 1] = "▲";
			Arrow[Arrow["▼"] = -1] = "▼";
			return Arrow;
		});
	}

	static get Direction() {
		return Plushie.Utils.getPrivateProperty(this, "Direction", () => {
			const Direction = Plushie.Utils.definePrivateProperties(window.Object.create(window.Object.prototype), "keys", {
				isValid: { enumerable: true, value: function isValid(value) { return this.keys.has(value); } },
				keys: { enumerable: true, get: function() { return Plushie.Utils.getPrivateProperty(this, "keys", () => new window.Set(window.Object.getOwnPropertyNames(this).map((key) => Plushie.Utils.numericIntCheck(key)))); } },
				toNumber: { enumerable: true, value: function toNumber(direction) { return Plushie.Utils.numericIntCheck(direction, undefined, this[direction]) | 0; } },
				toString: { enumerable: true, value: function toString(direction) { return Plushie.Utils.numericIntCheck(direction, this[direction]).toString(); } }
			});
			Direction[Direction.Ascending = 1] = "Ascending";
			Direction[Direction.Descending = -1] = "Descending";
			return Direction;
		});
	}

	constructor(direction) {
		super();
		if (typeof direction !== "number" && typeof direction !== "string")
			return;
		Plushie.Utils.definePrivateProperties(this, "sorter", {
			direction: { enumerable: true, value: Sorter.Direction.toNumber(direction) },
			static: { value: Sorter }
		});
	}

	get arrow() { return this.static.Arrow[this.direction]; }
	get isSelected() { return this.sorter.getAttribute("aria-checked") === "true"; }
	get name() { return this.static.Direction[this.direction]; }

	get sorter() {
		return Plushie.Utils.getPrivateProperty(this, "sorter", () => {
			const sorter = Plushie.Utils.createElement("span", { ["aria-checked"]: false, role: "radio", tabindex: 0, title: `Sort ${this.name}` }, undefined, this.arrow);
			sorter.addEventListener("click", (event) => this.sort());
			sorter.addEventListener("keydown", (event) => this.onKeyDown(event));
			return sorter;
		});
	}

	get value() { return this.sorter; }

	set isSelected(isSelected) { this.sorter.setAttribute("aria-checked", Boolean(isSelected)); }

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
				this.sort();
				break;
			default:
				return;
		}
		event.preventDefault();
	}

	sort() {
		this.isSelected = !this.isSelected;
		super.dispatchEvent(new window.CustomEvent("sort", { detail: { direction: (!this.isSelected) ? 0 : this.direction, sorter: this } }));
	}
});
Plushie.addEventListener("ready", function onReady(event) {
	Plushie.Utils.definePrivateProperties(Plushie.Plushmancer.Sorter, "Arrow", "Direction");
	window.Object.defineProperties(Plushie.Plushmancer, { Sorter: { configurable: false, enumerable: true, writable: false } });
	Plushie.removeEventListener("ready", onReady);
});
Plushie.registerScript("Plushmancer/Sorter");
