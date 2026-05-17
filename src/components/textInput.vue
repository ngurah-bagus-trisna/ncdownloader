<template>
  <div class="text-input-link">
    <input
      type="text"
      name="text-input-value"
      id="text-input-value"
      :placeholder="placeholder"
      :data-type="dataType"
    />
  </div>
</template>
<script>
export default {
  name: "textinput",
  props: {
    placeholder: String,
    dataType: String,
  },
};
</script>
<style scoped lang="scss">
.text-input-link {
  position: relative;
  width: 100%;
  flex: 2;
  input {
    width: 100%;
    height: 100%;
    margin: 0;
    border: 0;
    padding: 0;
    font-size: 14px;
    color: inherit;
    background: transparent;
    outline: none;
  }
}
@media only screen and (max-width: 1024px) {
  .text-input-link {
    width: 100%;
    input {
      width: 100%;
    }
  }
}
</style>
