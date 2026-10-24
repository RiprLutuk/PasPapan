fn main() {
    println!("cargo:rerun-if-changed=../../apps/api/migrations");
}
