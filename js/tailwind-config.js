window.tailwind = window.tailwind || {};
window.tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        "secondary-fixed-dim": "#a7c8ff",
        "on-secondary-fixed": "#001b3c",
        "surface-variant": "#e1e2e8",
        background: "#f8f9ff",
        "secondary-fixed": "#d5e3ff",
        "on-tertiary": "#ffffff",
        "surface-tint": "#1b60a2",
        "surface-dim": "#d9dae0",
        "surface-container-highest": "#e1e2e8",
        "surface-container": "#ededf4",
        "on-surface": "#191c20",
        "inverse-surface": "#2e3035",
        "on-primary-container": "#a4caff",
        "primary-container": "#005596",
        "tertiary-fixed-dim": "#ffb68a",
        secondary: "#3a5f94",
        "outline-variant": "#c1c7d2",
        "on-surface-variant": "#414750",
        error: "#ba1a1a",
        "on-primary-fixed-variant": "#004881",
        primary: "#003e6f",
        "on-primary-fixed": "#001c38",
        "surface-container-high": "#e7e8ee",
        outline: "#727781",
        "on-tertiary-fixed": "#321300",
        "on-tertiary-fixed-variant": "#743500",
        "on-error": "#ffffff",
        "on-background": "#191c20",
        "primary-fixed-dim": "#a2c9ff",
        surface: "#f8f9ff",
        "on-error-container": "#93000a",
        "on-secondary-container": "#294f83",
        "on-secondary": "#ffffff",
        "inverse-primary": "#a2c9ff",
        "surface-container-lowest": "#ffffff",
        "secondary-container": "#9fc2fe",
        "inverse-on-surface": "#eff0f6",
        "on-tertiary-container": "#ffb88c",
        "primary-fixed": "#d3e4ff",
        "on-primary": "#ffffff",
        "tertiary-container": "#873f01",
        "surface-bright": "#f8f9ff",
        "surface-container-low": "#f2f3f9",
        "tertiary-fixed": "#ffdbc8",
        "on-secondary-fixed-variant": "#1f477b",
        tertiary: "#642d00",
        "error-container": "#ffdad6"
      },
      borderRadius: {
        DEFAULT: "0.25rem",
        lg: "0.5rem",
        xl: "0.75rem",
        "2xl": "1.5rem",
        full: "9999px"
      },
      spacing: {
        "stack-gap": "12px",
        "margin-mobile": "20px",
        base: "4px",
        gutter: "16px",
        "card-padding": "24px"
      },
      fontFamily: {
        "headline-lg": ["Inter"],
        "status-label": ["Inter"],
        "headline-md": ["Inter"],
        "body-md": ["Inter"],
        "body-lg": ["Inter"],
        "label-md": ["Inter"]
      },
      fontSize: {
        "headline-lg": ["28px", { lineHeight: "34px", letterSpacing: "-0.02em", fontWeight: "700" }],
        "status-label": ["13px", { lineHeight: "18px", fontWeight: "500" }],
        "headline-md": ["20px", { lineHeight: "28px", letterSpacing: "-0.01em", fontWeight: "600" }],
        "body-md": ["14px", { lineHeight: "20px", fontWeight: "400" }],
        "body-lg": ["16px", { lineHeight: "24px", fontWeight: "400" }],
        "label-md": ["12px", { lineHeight: "16px", letterSpacing: "0.05em", fontWeight: "600" }]
      }
    }
  }
};