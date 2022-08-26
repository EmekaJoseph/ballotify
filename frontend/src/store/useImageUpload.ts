import { ref, watch, reactive, computed } from 'vue'

export function useImageUpload() {
    const imageFile = ref<any>('')
    const imageURL = ref<any>('')

    const imgSize = reactive({
        kb: computed(() => {
            return (imageFile.value.size / 1024).toFixed(2)
        }),
        mb: computed(() => {
            return (imgSize.kb / 1024).toFixed(2)
        })
    })

    function handleFileUpload(event) {
        if (event.target.files.length === 0) {
            imageFile.value = ''
            imageURL.value = ''
            return
        }
        imageFile.value = event.target.files[0]
    }

    watch(imageFile, (imageFile) => {
        if (!(imageFile instanceof File)) {
            return;
        }
        let fileReader = new FileReader
        fileReader.readAsDataURL(imageFile)
        fileReader.addEventListener('load', () => {
            imageURL.value = fileReader.result
        })
    })

    return { handleFileUpload, imageURL, imageFile, imgSize }
}