import ace from 'ace-builds'
import 'ace-builds/src-noconflict/mode-ini'

export default ({
    maxLines,
    minLines,
    fontSize,
}) => ({
    /** @type {ace.Ace.Editor} */
    editor: null,
    updateContentHandler: null,

    init() {
        this.editor = ace.edit(this.$refs.editor, {
            mode: 'ace/mode/ini',
            readOnly: true,
            maxLines,
            minLines,
            fontSize
        });

        this.updateContentHandler = (e) => {
            this.editor.session.setValue(e.detail.content)
        }

        window.addEventListener('logContentUpdated', this.updateContentHandler)
    },

    destroy() {
        if (this.updateContentHandler) {
            window.removeEventListener('logContentUpdated', this.updateContentHandler)
            this.updateContentHandler = null
        }

        this.editor?.destroy()
        this.editor = null
    },

    jumpToEnd() {
        this.editor.gotoLine(this.editor.session.doc.$lines.length)
    },

    jumpToStart() {
        this.editor.gotoLine(0)
    }
})
