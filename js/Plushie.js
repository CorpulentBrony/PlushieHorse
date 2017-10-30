"use strict";
// How about re-writing to use MutationObserver to let the classes be more independent? https://developer.mozilla.org/en-US/docs/Web/API/MutationObserver
// DO I HAVE TO RE-DO THE LISTBOX THING USING BUTTON TYPE=MENU?  https://www.w3.org/TR/html/sec-forms.html#element-attrdef-button-type
// aria info: https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA
// aria reference: http://w3c.github.io/html-aria/#allowed-aria-roles-states-and-properties
// how to build custom form widgets: https://developer.mozilla.org/en-US/docs/Learn/HTML/Forms/How_to_build_custom_form_widgets

window.Object.defineProperties(Plushie, {
	_KEY_CODES: { value: undefined, writable: true },
	KEY_CODES: { enumerable: true, get: () => (Plushie._KEY_CODES !== undefined) ? Plushie._KEY_CODES : Plushie._KEY_CODES = new window.Map(
		[9, "Tab"], [13, "Enter"], [27, "Escape"], [32, "Spacebar"], [35, "End"], [36, "Home"], [38, "ArrowUp"], [40, "ArrowDown"]
	)}
});
let plushie;
Plushie.addEventListener("ready", function onReady(event) {
	Plushie.Utils.setImmediate(() => {
		window.console.log("hey, i just got notified we're ready to go!");
		plushie = new Plushie.Plushmancer.Table();
		window.console.log(plushie.init());
		Plushie.removeEventListener("ready", onReady);
	});
});
Plushie.registerScript("Plushie");