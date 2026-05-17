<template>
  <div class="system-info-wrapper section">
    <h3 class="section-title">System Info</h3>
    <div class="system-info">
      <div class="system-info-item">
        <span class="system-info-label">Aria2 Version:</span>
        <span class="system-info-value">{{ aria2Ver }}</span>
      </div>
      <div class="system-info-item">
        <span class="system-info-label">Yt-dlp Version:</span>
        <span class="system-info-value">{{ ytdVer }}</span>
        <actionButton action="check" btnType="ytd" @clicked="checkUpdate" enableLoading="true"
          className="check-button">
          {{ ytdBtn }}
        </actionButton>
      </div>
    </div>
  </div>
</template>
<script>
import helper from "../utils/helper";
import actionButton from "./actionButton";

const ARIA2_CHECK_URL = "/apps/ncdownloader/aria2/release/check";
const ARIA2_UPDATE_URL = "/apps/ncdownloader/aria2/release/update";
const YTD_CHECK_URL = "/apps/ncdownloader/ytdl/release/check";
const YTD_UPDATE_URL = "/apps/ncdownloader/ytdl/release/update";

export default {
  name: "systemInfo",
  data() {
    return {
      aria2Btn: "Check for Update",
      ytdBtn: "Check for Update",
    };
  },
  components: { actionButton },
  methods: {
    checkUpdate(event, $vm) {
      const { btnType, action } = $vm.$props;
      const path = action === "check"
        ? (btnType === "aria2" ? ARIA2_CHECK_URL : YTD_CHECK_URL)
        : (btnType === "aria2" ? ARIA2_UPDATE_URL : YTD_UPDATE_URL);
      helper
        .httpClient(helper.generateUrl(path))
        .setMethod("GET")
        .setHandler((data) => {
          $vm.loading = false;
          if (data.status) {
            helper.info(data.message);
            if (action == "check") {
              if (btnType == "ytd") this.ytdBtn = "Update";
              else this.aria2Btn = "Update";
              $vm.$props.action = "update";
            } else {
              if (btnType == "ytd") this.ytdBtn = "Check for Update";
              else this.aria2Btn = "Check for Update";
              $vm.$props.action = "check";
              if (data.data) {
                if (btnType == "ytd") this.ytdVer = data.data;
                else if (btnType == "aria2") this.aria2Ver = data.data;
              }
            }
          } else {
            helper.info(data.message);
          }
        })
        .send();
    },
  },
  props: {
    aria2Version: { type: String, default: "" },
    ytdVersion: { type: String, default: "" },
  },
};
</script>
<style scoped lang="scss">
.system-info-wrapper {
  margin-top: 16px;

  .section-title {
    color: var(--color-main-text);
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
  }
}

.system-info {
  display: flex;
  flex-direction: column;
  gap: 8px;

  .system-info-item {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .system-info-label {
    font-weight: 600;
    color: var(--color-main-text);
    min-width: 120px;
  }

  .system-info-value {
    color: var(--color-text-maxcontrast);
    font-family: monospace;
  }

  .check-button {
    margin-left: 8px;
  }
}
</style>
