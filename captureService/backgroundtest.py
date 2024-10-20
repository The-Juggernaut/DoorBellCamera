import numpy as np
import cv2

cap = cv2.VideoCapture('test.mp4')
fgbg = cv2.createBackgroundSubtractorMOG2()

while(1):
    ret, frame = cap.read()
    gray = cv2.GaussianBlur(frame, (21, 21), 0)
    fgmask = fgbg.apply(gray)

    cv2.imshow('frame',fgmask)
    k = cv2.waitKey(30) & 0xff
    if k == 27:
        break

cap.release()
cv2.destroyAllWindows()