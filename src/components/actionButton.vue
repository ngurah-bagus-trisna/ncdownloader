<template>
  <button ref="button" :class="className" v-if="!loading" @click.prevent="handler">
    <slot>Download</slot>
  </button>
  <button class="loading-btn" v-if="loading" disabled>
    <span class="spinner"></span>
    Loading...
  </button>
</template>
<script>
export default {
  data() {
    return {
      loading: false,
    };
  },
  methods: {
    handler(event) {
      if (this.enableLoading) this.loading = true;
      this.$emit("clicked", event, this);
    },
  },
  name: "actionButton",
  props: {
    className: String,
    enableLoading: {
      type: Boolean,
      default: false,
    },
  },
};
</script>
<style lang="scss" scoped>
button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 16px;
    border-radius: var(--border-radius);
    font-weight: 500;
    cursor: pointer;
    border: none;
    font-size: 14px;
    min-height: 34px;
}

button:not(.loading-btn) {
    background-color: var(--color-primary);
    color: var(--color-primary-text);

    &:hover {
        filter: brightness(1.1);
    }
}

.loading-btn {
    background-color: var(--color-background-dark);
    color: var(--color-text-maxcontrast);
    cursor: not-allowed;
    opacity: 0.7;
}

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid var(--color-border);
    border-top: 2px solid var(--color-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
