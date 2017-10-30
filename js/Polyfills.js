/**
 * This will just create a class that supports the value property of HTMLDataElement.  To fully polyfill, you'd probably also need to intercept calls to 
 * document.createElement and the document.getElement* functions and check for data elements in the DOM.
 */
// if (window.HTMLDataElement === undefined)
// 	window.HTMLDataElement = class HTMLDataElement extends window.HTMLElement {
// 		get value() { return super.getAttribute("value"); }

// 		set value(value) { super.setAttribute("value", value); }
// 	}
Plushie.registerScript("Polyfills");