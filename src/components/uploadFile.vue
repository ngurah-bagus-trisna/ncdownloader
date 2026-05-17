<template>
  <form class="upload-file-form" :action="path">
    <label class="icon-upload fileinput">
      <input type="file" name="torrentfile" accept=".torrent" @change="handler" />
    </label>
  </form>
</template>
<script>
export default {
  name: "uploadFile",
  methods: {
    handler(event) {
      this.$emit("uploadfile", event, this);
    },
  },
  props: ["path"],
};
</script>
<style scoped lang="scss">
.upload-file-form {
  height: 100%;
  .icon-upload {
    background-image: url("../../img/upload.svg");
  }
  .fileinput {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    width: 44px;
    background-color: var(--color-main-background);
    background-position: center;
    background-repeat: no-repeat;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    cursor: pointer;
    &:hover {
      background-color: var(--color-background-hover);
    }
    input[type="file"] {
      cursor: pointer;
      opacity: 0;
      width: 100%;
      height: 100%;
    }
  }
}
</style>
