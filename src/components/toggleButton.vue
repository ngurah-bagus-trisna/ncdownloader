<template>
  <label :for="name" :class="{ active: isActive }" class="toggle-button">
    <span class="toggle-label">{{ text }}</span>
    <input
      type="checkbox"
      :disabled="disabled"
      :id="name"
      :name="name"
      :value="value"
      v-model="inputValue"
    />
    <span class="toggle-switch"></span>
  </label>
</template>

<script>
export default {
  name: "toggleButton",
  props: {
    disabled: { type: Boolean, default: false },
    enabledText: { type: String, default: "On" },
    disabledText: { type: String, default: "Off" },
    name: { type: String, default: "check-button" },
    defaultStatus: { type: Boolean, default: false },
  },
  data() {
    return { status: this.defaultStatus };
  },
  watch: {
    defaultStatus() {
      this.status = Boolean(this.defaultStatus);
    },
  },
  computed: {
    isActive() { return this.status; },
    text() { return this.status ? this.disabledText : this.enabledText; },
    inputValue: {
      get() { return this.status; },
      set(value) {
        this.status = value;
        this.$emit("changed", this.name, value);
      },
    },
  },
};
</script>

<style scoped lang="scss">
$toggle-height: 24px;
$toggle-width: 44px;

.toggle-button {
  user-select: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.toggle-label {
  font-size: 14px;
  color: var(--color-main-text);
}

.toggle-button input[type="checkbox"] {
  opacity: 0;
  position: absolute;
  width: 1px;
  height: 1px;
}

.toggle-button .toggle-switch {
  display: inline-block;
  height: $toggle-height;
  border-radius: $toggle-height;
  width: $toggle-width;
  background: var(--color-background-dark);
  box-shadow: inset 0 0 2px rgba(0,0,0,0.15);
  position: relative;
  transition: background 0.2s;

  &::after {
    content: "";
    position: absolute;
    display: block;
    height: $toggle-height - 4px;
    width: $toggle-height - 4px;
    border-radius: 50%;
    left: 2px;
    top: 2px;
    background: var(--color-main-background);
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    transition: transform 0.2s ease;
  }
}

.active .toggle-switch {
  background: var(--color-primary);
  &::after {
    transform: translateX($toggle-width - $toggle-height);
  }
}
</style>
