import styles from "./page.module.css";
import Slider from "@/components/Slider";

import "./page.module.css";

export default function Home() {
  return (
    <main className={styles.main}>
      <Slider className="slider" />
    </main>
  );
}
