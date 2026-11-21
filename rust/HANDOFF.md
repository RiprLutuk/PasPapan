# Migration handoff

Read `MAP.md` and `DECISIONS.md` before changing a unit. The currently selected unit is user
import/export. Validate every change with `cargo fmt --check`, `cargo clippy --workspace -- -D
warnings`, `cargo test --workspace`, `bun run check`, and `bun run test:web`.
