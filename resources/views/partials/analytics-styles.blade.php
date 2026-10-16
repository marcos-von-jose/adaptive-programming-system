<style>
    .analytics-page { --analytics-track: #e8edf2; --analytics-blue: #4c78a8; --analytics-amber: #df9145; --analytics-green: #318464; }
    .analytics-page .page-heading { margin-bottom: 24px; }
    .analytics-kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 18px; }
    .analytics-card { min-width: 0; padding: 23px; border: 1px solid var(--line); border-radius: 20px; background: var(--paper); box-shadow: 0 12px 30px rgba(23, 32, 51, .035); }
    .analytics-card-label { margin: 0 0 15px; color: var(--muted); font-size: 12px; font-weight: 750; }
    .analytics-card-value { margin: 0; color: var(--ink); font-size: 31px; font-weight: 800; letter-spacing: -.05em; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .analytics-card-note { min-height: 18px; margin: 6px 0 0; color: var(--muted); font-size: 11px; line-height: 1.5; }
    .analytics-track { height: 9px; overflow: hidden; border-radius: 999px; background: var(--analytics-track); }
    .analytics-fill { display: block; height: 100%; border-radius: inherit; background: var(--analytics-blue); }
    .analytics-card .analytics-track { margin-top: 19px; }
    .analytics-card-footer { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 5px 10px; margin-top: 15px; padding-top: 12px; border-top: 1px solid var(--line); color: var(--muted); font-size: 11px; }
    .analytics-card-footer strong { color: var(--ink); }
    .analytics-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; margin-bottom: 18px; }
    .analytics-page .panel { min-width: 0; }
    .analytics-page .panel-header { margin-bottom: 18px; }
    .analytics-page .panel-header p { margin: 4px 0 0; color: var(--muted); font-size: 11px; }
    .analytics-bars { display: grid; gap: 17px; }
    .analytics-bar-row { display: grid; grid-template-columns: minmax(95px, 1fr) minmax(75px, 1.4fr) 45px; align-items: center; gap: 12px; }
    .analytics-bar-label { min-width: 0; overflow-wrap: anywhere; color: var(--ink); font-size: 12px; font-weight: 700; }
    .analytics-bar-value { color: var(--ink); font-size: 12px; font-weight: 800; text-align: right; font-variant-numeric: tabular-nums; }
    .analytics-columns { display: flex; height: 198px; align-items: stretch; gap: 12px; padding: 0 5px 10px; border-bottom: 1px solid var(--line); }
    .analytics-column { display: flex; min-width: 0; flex: 1; flex-direction: column; align-items: center; justify-content: end; }
    .analytics-column-value { margin-bottom: 6px; color: var(--ink); font-size: 11px; font-weight: 800; font-variant-numeric: tabular-nums; }
    .analytics-column-track { display: flex; width: min(100%, 44px); height: 125px; align-items: end; }
    .analytics-column-fill { display: block; width: 100%; height: max(4px, var(--bar-height)); border-radius: 7px 7px 0 0; background: var(--analytics-amber); }
    .analytics-column-label { margin-top: 9px; color: var(--muted); font-size: 10px; font-weight: 700; text-align: center; }
    .analytics-section { margin-bottom: 18px; }
    .analytics-page .analytics-score { min-width: 140px; }
    .analytics-page .analytics-score .analytics-track { height: 5px; margin-top: 7px; }
    .analytics-page .analytics-score .analytics-fill { background: var(--analytics-green); }
    .analytics-page .analytics-count { min-width: 115px; }
    .analytics-page .analytics-count .analytics-track { height: 5px; margin-top: 7px; }
    .analytics-page .analytics-count .analytics-fill { background: var(--analytics-amber); }
    .analytics-print-date { display: none; }
    html[data-theme="dark"] .analytics-page { --analytics-track: #34465b; --analytics-blue: #8eb2da; --analytics-amber: #f0b36b; --analytics-green: #65be97; }
    @media (max-width: 1100px) { .analytics-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 850px) { .analytics-grid { grid-template-columns: 1fr; } }
    @media (max-width: 620px) { .analytics-kpis { grid-template-columns: 1fr; } .analytics-card { padding: 20px; } .analytics-bar-row { grid-template-columns: minmax(0, 95px) minmax(0, 1fr) 40px; gap: 8px; } .analytics-columns { gap: 5px; } }
    @media print {
        @page { size: A4; margin: 12mm; }
        html, html[data-theme="dark"] { color-scheme: light; --ink: #172033; --muted: #586174; --line: #d8dee6; --paper: #fff; --canvas: #fff; }
        body { background: #fff !important; color: #172033 !important; }
        .sidebar, .backdrop, .topbar, .site-footer, .analytics-print-button { display: none !important; }
        .app { margin: 0 !important; min-height: 0; }
        .content { width: 100% !important; padding: 0 !important; }
        .analytics-page, html[data-theme="dark"] .analytics-page { --analytics-track: #e8edf2; --analytics-blue: #4c78a8; --analytics-amber: #df9145; --analytics-green: #318464; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .analytics-page .page-heading { margin-bottom: 12px; }
        .analytics-print-date { display: block; margin: 4px 0 0; color: #586174; font-size: 11px; }
        .analytics-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
        .analytics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .analytics-card, .analytics-page .panel { padding: 14px; border-color: #d8dee6; box-shadow: none; break-inside: avoid; }
        .analytics-card-value { font-size: 24px; }
        .analytics-columns { height: 150px; }
        .analytics-column-track { height: 95px; }
        .analytics-page tr { break-inside: avoid; }
    }
</style>
