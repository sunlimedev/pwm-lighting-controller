# create byte lists from quadratic/exponential functions to be used as lookup tables
import numpy as np

x = np.linspace(0, 100, 100, endpoint=False, dtype=float)

y_float = []

for i in range(0, 10):
    y_float.append(round( ((0.963/81)*(i**2) + (3/81)) , ndigits = 4))

for i in range(10, 100):
    y_float.append(round((float(np.exp((-0.04*i)+0.4))), ndigits = 4))

y_int = []

for i in range(0, 100):
    y_int.append(hex(round( y_float[i] * 65535 )))

for i in range(0, 100):
    print(y_int[i], end = ", ")
