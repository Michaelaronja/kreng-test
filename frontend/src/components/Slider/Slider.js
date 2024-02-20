"use client";

import React, { useState, useEffect } from "react";
import styles from "./Slider.module.css";
import classes from "clsx";
import { ArrowRight, ArrowLeft } from "@/components/icons";

const Slider = () => {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [slides, setSlides] = useState([]);
  const [isLoading, setIsLoading] = React.useState(false);

  useEffect(() => {
    const fetchSlidesData = async () => {
      setIsLoading(true);
      try {
        const response = await fetch(
          "http://localhost:8000/wp-json/wp/v2/movie"
        );
        if (!response.ok) {
          throw new Error("Failed to fetch data");
        }
        const data = await response.json();
        setSlides(data);
      } catch (error) {
        console.error("Error fetching slides:", error);
      } finally {
        setIsLoading(false);
      }
    };

    fetchSlidesData();
  }, []);

  const nextSlide = () => {
    setCurrentIndex((prevIndex) =>
      prevIndex === slides?.length - 1 ? 0 : prevIndex + 1
    );
  };

  const prevSlide = () => {
    setCurrentIndex((prevIndex) =>
      prevIndex === 0 ? slides?.length - 1 : prevIndex - 1
    );
  };

  return (
    <div>
      {isLoading && <div className={styles.loading}>Is loading...</div>}
      {slides.map((slide, idx) => (
        <div
          key={idx}
          className={classes({
            [styles.slide]: true,
            [styles.active]: idx === currentIndex,
          })}
        >
          <h1 className={styles.slideTitle}>{slide.title.rendered}</h1>
          <img
            className={styles.slideImage}
            src={slide.meta.image_url}
            alt={`Slide ${idx}`}
          />
        </div>
      ))}

      {slides.length > 0 && (
        <div className={styles.paginationContainer}>
          <button className={styles.button} onClick={prevSlide}>
            <ArrowLeft />
            <span>Prev</span>
          </button>

          <button
            className={classes(styles.button, styles.buttonNext)}
            onClick={nextSlide}
          >
            <span>Next</span>
            <ArrowRight />
          </button>
        </div>
      )}
    </div>
  );
};

export default Slider;
