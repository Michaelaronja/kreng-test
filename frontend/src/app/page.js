import styles from "./page.module.css";
import Slider from "@/components/Slider";

export default function Home() {
  return (
    <main className={styles.main}>
      <Slider />
    </main>
  );
}
