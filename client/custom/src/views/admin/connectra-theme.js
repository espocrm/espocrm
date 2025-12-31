define('custom:views/admin/connectra-theme', ['views/settings/record/edit'], function (SettingsEditRecordView) {

    return class ConnectraThemeView extends SettingsEditRecordView {

        layoutName = 'connectraTheme'

        saveAndContinueEditingAction = false

        setup() {
            super.setup();

            this.listenTo(this.model, 'change', () => {
                this.applyPreviewStyles();
            });

            this.on('save', () => {
                this.setConfirmLeaveOut(false);
                window.location.reload();
            });
        }

        afterRender() {
            super.afterRender();
            this.applyPreviewStyles();
            this.injectDynamicCss();
        }

        applyPreviewStyles() {
            const root = document.documentElement;

            const colorMappings = {
                'flavourTextColor': '--text-color',
                'flavourBackgroundColor': '--body-bg',
                'flavourBrandPrimary': '--brand-primary',
                'flavourBrandInfo': '--brand-info',
                'flavourBrandDanger': '--brand-danger',
                'flavourBrandSuccess': '--brand-success',
                'flavourBrandWarning': '--brand-warning',
                'flavourNavbarColor': '--navbar-bg',
                'flavourTabsMenuBgColor': '--navbar-inverse-bg',
                'flavourLogoBackgroundColor': '--connectra-logo-bg'
            };

            Object.entries(colorMappings).forEach(([field, cssVar]) => {
                const value = this.model.get(field);
                if (value) {
                    root.style.setProperty(cssVar, value);
                }
            });

            // Apply logo position
            const logoPosition = this.model.get('flavourLogoPosition');
            document.body.classList.remove('logo-position-left', 'logo-position-center', 'logo-position-right');
            if (logoPosition) {
                document.body.classList.add('logo-position-' + logoPosition);
            }

            // Apply hover expand class
            if (this.model.get('flavourSideMenuHoverExpand')) {
                document.body.classList.add('connectra-hover-expand');
            } else {
                document.body.classList.remove('connectra-hover-expand');
            }

            // Apply sidebar menu styles
            this.applySidebarPreviewStyles();
        }

        applySidebarPreviewStyles() {
            // Remove existing preview style
            let existingStyle = document.getElementById('connectra-sidebar-preview');
            if (existingStyle) {
                existingStyle.remove();
            }

            let css = '';

            // Menu icon color
            const iconColor = this.model.get('flavourSidenavIconColor');
            if (iconColor) {
                css += `body[data-navbar='side'] #navbar ul.tabs > li > a > span.short-label > span { color: ${iconColor} !important; }`;
                css += `body[data-navbar='side'] #navbar ul.tabs > li > a > span.short-label > span.short-label-text { color: ${iconColor} !important; }`;
            }

            // Menu text color
            const textColor = this.model.get('flavourSidenavTextColor');
            if (textColor) {
                css += `body[data-navbar='side'] #navbar ul.tabs > li > a { color: ${textColor} !important; }`;
                css += `body[data-navbar='side'] #navbar ul.tabs > li > a > span.full-label { color: ${textColor} !important; }`;
            }

            // Menu font size
            const fontSize = this.model.get('flavourSidenavFontSize');
            if (fontSize && fontSize !== '14') {
                css += `body[data-navbar='side'] #navbar ul.tabs > li > a { font-size: ${fontSize}px !important; }`;
            }

            // Menu font weight
            const fontWeight = this.model.get('flavourSidenavFontWeight');
            if (fontWeight && fontWeight !== 'normal') {
                css += `body[data-navbar='side'] #navbar ul.tabs > li > a { font-weight: ${fontWeight} !important; }`;
            }

            // Active icon color
            const activeIconColor = this.model.get('flavourSidenavActiveIconColor');
            if (activeIconColor) {
                css += `body[data-navbar='side'] #navbar ul.tabs > li.active > a > span.short-label > span { color: ${activeIconColor} !important; }`;
            }

            // Active text color
            const activeFontColor = this.model.get('flavourSidenavActiveFontColor');
            if (activeFontColor) {
                css += `body[data-navbar='side'] #navbar ul.tabs > li.active > a { color: ${activeFontColor} !important; }`;
                css += `body[data-navbar='side'] #navbar ul.tabs > li.active > a > span.full-label { color: ${activeFontColor} !important; }`;
            }

            // Active background color
            const activeBgColor = this.model.get('flavourSidenavActiveBgColor');
            if (activeBgColor) {
                css += `body[data-navbar='side'] #navbar ul.tabs > li.active > a { background-color: ${activeBgColor} !important; }`;
            }

            if (css) {
                const style = document.createElement('style');
                style.id = 'connectra-sidebar-preview';
                style.textContent = css;
                document.head.appendChild(style);
            }
        }

        injectDynamicCss() {
            // Inject Connectra theme CSS
            let link = document.getElementById('connectra-theme-css');
            if (!link) {
                link = document.createElement('link');
                link.id = 'connectra-theme-css';
                link.rel = 'stylesheet';
                link.href = 'client/custom/css/connectra/connectra.css';
                document.head.appendChild(link);
            }

            // Inject dynamic CSS from entry point
            let dynamicLink = document.getElementById('connectra-dynamic-css');
            if (!dynamicLink) {
                dynamicLink = document.createElement('link');
                dynamicLink.id = 'connectra-dynamic-css';
                dynamicLink.rel = 'stylesheet';
                dynamicLink.href = '?entryPoint=ConnectraThemeCss&r=' + Date.now();
                document.head.appendChild(dynamicLink);
            }
        }
    };
});
