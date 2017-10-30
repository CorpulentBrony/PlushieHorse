"use strict";
Plushie.TypeAheadBuffer = class TypeAheadBuffer {
	constructor() {
		Plushie.Utils.definePrivateProperties(this, "buffer", {
			timeoutId: { enumerable: true, value: undefined, writable: true }
		});
	}

	get buffer() { return Plushie.Utils.getPrivateProperty(this, "buffer", () => new window.Array()); }

	_timeoutCallback() { this.reset(); }

	add(character) {
		this.buffer.push(character.toString().slice(0, 1).toLowerCase());
		window.clearTimeout(this.timeoutId);
		this.timeoutId = window.setTimeout(this._timeoutCallback.bind(this), TypeAheadBuffer.LIFETIME_MS);
		return this;
	}

	reset() { this.buffer.length = 0; }
	toString() { return this.buffer.join(""); }
	[Symbol.toPrimitive](hint) { return (hint === "number") ? this.buffer.length : this.toString(); }
}
window.Object.defineProperties(Plushie.TypeAheadBuffer, { LIFETIME_MS: { enumerable: true, get: () => 5000 }});
window.Object.defineProperties(Plushie, { TypeAheadBuffer: { configurable: false, enumerable: true, writable: false } });
Plushie.registerScript("TypeAheadBuffer");