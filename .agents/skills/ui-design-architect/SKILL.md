---
name: ui-design-architect
description: Design and refine clean, organized user interfaces with spatial common sense, intentional button placement, typographic hierarchy, tasteful 3D depth, animated charts and graphs, and smooth page transitions. Use when building, styling, or reviewing web or mobile UI, dashboards, layouts, buttons, charts, animations, or navigation transitions.
---

Instructions
UI Design Architect
Build clean, organized interfaces where every control sits where users naturally expect it, typography guides the eye, subtle 3D depth adds clarity, and every chart and page switch moves smoothly.

When to Use
Creating or redesigning web pages, mobile screens, dashboards, or components
Fixing cluttered layouts, awkward button placements, or inconsistent spacing
Adding data visualizations, charts, or graphs that need entry and update animations
Implementing navigation, tab switches, or multi-page routes that need smooth transitions
Reviewing existing frontend code (HTML/CSS/JS, Tailwind, React, Laravel/Blade, or Flutter) for visual polish and UX common sense
Core Design Standards
1. Spatial Common Sense and Organization
Follow an 8px spacing scale (4px, 8px, 16px, 24px, 32px, 48px) so padding and gaps stay consistent across the entire screen.
Group related labels, inputs, and actions closer together than unrelated sections (law of proximity).
Leave intentional whitespace around cards, headers, and action bars so the layout feels calm rather than cramped.
Align elements strictly to an underlying grid; avoid floating or arbitrarily offset boxes.
2. Intentional Button Placement
Place primary actions in predictable locations: top-right of page headers for creation actions ("New Item"), bottom-right of forms and dialogs for confirmation ("Save", "Submit"), and persistent bottom bars on mobile views.
Limit each section or modal to one high-emphasis primary button; pair secondary actions ("Cancel", "Reset") as ghost or outline buttons to the left of the primary button.
Separate destructive actions ("Delete", "Remove") spatially from save actions and style them with muted danger cues until hovered or confirmed.
Keep interactive touch targets at least 44x44px with clear hover, focus-visible, active-press, and disabled states.
3. Typography and Visual Hierarchy
Establish a clear 4-tier scale: Page Title, Section Heading, Body Copy, and Metadata/Caption.
Differentiate hierarchy using font weight, size, and muted secondary text colors rather than excessive borders.
Constrain prose width to 60-75 characters per line and use tabular-nums on numbers, timers, KPIs, and chart axes so values do not jitter during animations.
4. Tasteful 3D Depth and Surface Layering
Use a single consistent top-down light source: elevated cards and floating toolbars get crisp subtle borders plus soft layered shadows, while recessed inputs sit slightly darker or inset.
Use subtle 3D perspective, glassmorphism, or card-tilt micro-interactions only on focal hero cards or interactive tiles to signal depth without obscuring text legibility.
Maintain clear surface contrast between background canvas, card containers, and elevated overlays (modals, dropdowns, tooltips).
5. Animated Graphs and Data Visualizations
Always animate charts on initial render and when data filters change:
Bar charts: Grow smoothly from the zero baseline with a slight stagger across bars (30-50ms per bar).
Line and area charts: Draw the stroke path from left to right while fading in the gradient fill beneath.
Donut and radial charts: Sweep arc segments from 0 degrees to their target angle.
KPI summary numbers: Count up smoothly from the previous value to the target value.
Provide interactive hover or tap crosshairs and floating tooltips that follow the pointer smoothly.
6. Page and View Transitions
Never allow abrupt, instant cuts when switching pages, routes, tabs, or modals:
Page-to-page navigation: Use directional slide-and-fade or shared-element transitions (View Transitions API, Framer Motion, or Flutter Hero/PageRouteBuilder).
Tab and filter switches: Animate an active indicator pill sliding smoothly to the selected tab while cross-fading panel content.
Modals and drawers: Scale up gently (from 0.96 to 1.0) with a backdrop fade-in on open, and reverse smoothly on close.
Keep UI transition durations snappy (180ms-320ms with ease-out curves for entrances, 150ms-220ms for exits) and respect prefers-reduced-motion.
Execution Workflow
Structure the Grid: Define the layout skeleton, containers, and 8px spacing rhythm before adding styling details.
Anchor Controls: Place navigation, headers, and primary/secondary buttons according to user reading flow and thumb/cursor ergonomics.
Apply Typography and Depth: Set the type scale, contrast hierarchy, and layered card surfaces.
Wire Motion and Charts: Add staggered graph animations, interactive tooltips, micro-interactions on buttons, and route/tab transitions.
Verify Ergonomics: Check mobile and desktop breakpoints to ensure no overlapping buttons, clipped labels, or janky animations.
Gotchas
Never place a primary submit button on the far left of a multi-step form where users might confuse it with "Back".
Never animate layout-thrashing properties (width, height, top, left) for page transitions; stick to GPU-composited transform and opacity.
Never leave graphs static on load when building dashboards; if a charting library disables animation by default, explicitly enable entry and update transitions.