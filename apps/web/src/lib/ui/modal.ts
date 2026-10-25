/** Native modal: traps keyboard focus, makes the page inert and restores focus. */
export function modal(node: HTMLDialogElement) {
	node.showModal();
	return { destroy() { if (node.open) node.close(); } };
}
